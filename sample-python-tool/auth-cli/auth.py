#!/usr/bin/env python3
"""Pantheon Dashboard authentication CLI.

Tries multiple Auth0 authentication strategies to obtain Hermes session cookies:
  1. Cross-origin auth (/co/authenticate + login_ticket)
  2. New Universal Login form submission (/u/login)

Prints the values of X-Pantheon-Access-Token and X-Pantheon-Session.
"""

import getpass
import re
import sys
from urllib.parse import urlparse, parse_qs, urljoin, urlencode

import requests
from bs4 import BeautifulSoup

ENVIRONMENTS = {
    "production": {
        "auth0_domain": "pantheon.auth0.com",
        "hermes_url": "https://dashboard.pantheon.io",
    },
    "sandbox": {
        "auth0_domain": "pantheon-prodmirror.us.auth0.com",
        "hermes_url": "https://hermes.sandbox-fast.sbx04.pantheon.io",
    },
}

AUTH0_CONNECTION = "Pantheon"
TARGET_COOKIES = ["X-Pantheon-Access-Token", "X-Pantheon-Session"]
MAX_HOPS = 20


def die(msg):
    print(f"Error: {msg}", file=sys.stderr)
    sys.exit(1)


def collect_target_cookies(session):
    found = {}
    for cookie in session.cookies:
        if cookie.name in TARGET_COOKIES:
            found[cookie.name] = cookie.value
    return found


def get_state(url):
    return parse_qs(urlparse(url).query).get("state", [None])[0]


def follow_full_chain(session, resp):
    """Follow redirects and auto-submit HTML forms until settled or cookies found."""
    for _ in range(MAX_HOPS):
        if len(collect_target_cookies(session)) == len(TARGET_COOKIES):
            return resp

        if resp.status_code in (301, 302, 303, 307, 308):
            location = resp.headers.get("Location", "")
            if not location:
                return resp
            next_url = urljoin(resp.url, location)
            p = urlparse(next_url)
            print(f"  → {resp.status_code} to {p.netloc}{p.path}")
            resp = session.get(next_url, allow_redirects=False, timeout=30)
            continue

        if resp.status_code == 200 and "text/html" in resp.headers.get("Content-Type", ""):
            soup = BeautifulSoup(resp.text, "html.parser")
            form = soup.find("form")
            if form and form.get("action"):
                action = form["action"]
                if not action.startswith("http"):
                    action = urljoin(resp.url, action)
                fields = {}
                for inp in form.find_all("input"):
                    name = inp.get("name")
                    if name:
                        fields[name] = inp.get("value", "")
                p = urlparse(action)
                print(f"  → form POST to {p.netloc}{p.path}")
                resp = session.post(action, data=fields, allow_redirects=False, timeout=30)
                continue
            return resp

        return resp
    return resp


def get_authorize_url(session, hermes_url):
    """Follow Hermes login redirect to capture the Auth0 /authorize URL."""
    resp = session.get(
        f"{hermes_url}/auth/providers/any/login",
        params={"destination": "/workspace"},
        allow_redirects=False,
        timeout=30,
    )

    for _ in range(10):
        if resp.status_code not in (301, 302, 303, 307, 308):
            break
        location = resp.headers.get("Location", "")
        next_url = urljoin(resp.url, location)
        if "/authorize" in urlparse(next_url).path:
            return next_url
        p = urlparse(next_url)
        print(f"  → {resp.status_code} to {p.netloc}{p.path}")
        resp = session.get(next_url, allow_redirects=False, timeout=30)

    return None


def try_cross_origin_auth(session, auth0_base, hermes_url, client_id, email, password):
    """Strategy 1: Auth0 cross-origin authentication."""
    print("\n[2a] Trying /co/authenticate...")
    co_resp = session.post(
        f"{auth0_base}/co/authenticate",
        json={
            "client_id": client_id,
            "credential_type": "http://auth0.com/oauth/grant-type/password-realm",
            "username": email,
            "password": password,
            "realm": AUTH0_CONNECTION,
        },
        headers={
            "Origin": hermes_url,
        },
        timeout=30,
    )

    if co_resp.status_code != 200:
        try:
            msg = co_resp.json().get("error_description", co_resp.json().get("error", co_resp.text[:200]))
        except Exception:
            msg = co_resp.text[:200]
        print(f"  /co/authenticate failed ({co_resp.status_code}): {msg}")
        return None

    data = co_resp.json()
    login_ticket = data.get("login_ticket")
    if not login_ticket:
        print(f"  No login_ticket in response: {data}")
        return None

    print(f"  Got login_ticket: {login_ticket[:20]}...")
    return login_ticket


def try_new_ul_login(session, auth0_base, login_page_resp, email, password):
    """Strategy 2: Auth0 New Universal Login form submission."""
    state = get_state(login_page_resp.url)
    page_path = urlparse(login_page_resp.url).path

    if "/u/login/identifier" in page_path:
        print("\n[2b] Submitting email to /u/login/identifier...")
        # Post with minimal fields only
        id_resp = session.post(
            f"{auth0_base}/u/login/identifier",
            data={"state": state, "username": email, "action": "default"},
            headers={"Origin": auth0_base, "Referer": login_page_resp.url},
            allow_redirects=False,
            timeout=30,
        )
        print(f"  Response: HTTP {id_resp.status_code}")

        if id_resp.status_code not in (302, 303):
            print(f"  Identifier step returned HTTP {id_resp.status_code}")
            return None

        # Follow to password page
        pw_resp = id_resp
        for _ in range(5):
            if pw_resp.status_code not in (301, 302, 303, 307, 308):
                break
            loc = pw_resp.headers.get("Location", "")
            next_url = urljoin(pw_resp.url, loc)
            p = urlparse(next_url)
            print(f"  → {pw_resp.status_code} to {p.netloc}{p.path}")
            pw_resp = session.get(next_url, allow_redirects=False, timeout=30)

        pw_state = get_state(pw_resp.url) or state
        pw_path = urlparse(pw_resp.url).path
        print(f"  Password page: {pw_path}")

        if "/u/login/identifier" in pw_path:
            print("  Redirected back to identifier — email not recognized?")
            return None

        print("  Submitting password to /u/login...")
        login_resp = session.post(
            f"{auth0_base}/u/login",
            data={"state": pw_state, "username": email, "password": password, "action": "default"},
            headers={"Origin": auth0_base, "Referer": pw_resp.url},
            allow_redirects=False,
            timeout=30,
        )
    else:
        print("\n[2b] Submitting credentials to /u/login...")
        login_resp = session.post(
            f"{auth0_base}/u/login",
            data={"state": state, "username": email, "password": password, "action": "default"},
            headers={"Origin": auth0_base, "Referer": login_page_resp.url},
            allow_redirects=False,
            timeout=30,
        )

    print(f"  Response: HTTP {login_resp.status_code}")
    return login_resp


def main():
    email = input("Email: ")
    password = getpass.getpass("Password: ")

    print("\nEnvironment:")
    print("  1) Production (dashboard.pantheon.io)")
    print("  2) Sandbox    (sandbox-fast.sbx04)")
    choice = input("Choice [1]: ").strip() or "1"
    env_name = "sandbox" if choice == "2" else "production"
    env = ENVIRONMENTS[env_name]
    auth0_base = f"https://{env['auth0_domain']}"

    print(f"\nAuthenticating against {env_name}...")

    session = requests.Session()
    session.headers["User-Agent"] = (
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
        "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    )

    # Step 1: Get authorize URL from Hermes
    print("\n[1] Getting Auth0 /authorize URL from Hermes...")
    authorize_url = get_authorize_url(session, env["hermes_url"])
    if not authorize_url:
        die("Could not find Auth0 /authorize redirect")

    auth_params = parse_qs(urlparse(authorize_url).query)
    client_id = auth_params.get("client_id", [None])[0]
    state = auth_params.get("state", [None])[0]
    print(f"  client_id={client_id[:8]}... state={state[:12]}...")

    # Strategy 1: Try cross-origin auth (cleanest for CLI)
    login_ticket = try_cross_origin_auth(session, auth0_base, env["hermes_url"], client_id, email, password)

    if login_ticket:
        # Use login_ticket with /authorize to complete the flow
        print("\n[3] Using login_ticket with /authorize...")
        ticket_url = authorize_url + f"&login_ticket={login_ticket}&realm={AUTH0_CONNECTION}"
        resp = session.get(ticket_url, allow_redirects=False, timeout=30)
        final_resp = follow_full_chain(session, resp)
    else:
        # Strategy 2: Fall back to New Universal Login form submission
        print("\n  Falling back to Universal Login form submission...")

        # Follow authorize URL to login page
        print("  Following /authorize to login page...")
        auth_resp = session.get(authorize_url, allow_redirects=False, timeout=30)
        login_page = auth_resp
        for _ in range(5):
            if login_page.status_code not in (301, 302, 303, 307, 308):
                break
            loc = login_page.headers.get("Location", "")
            next_url = urljoin(login_page.url, loc)
            p = urlparse(next_url)
            print(f"  → {login_page.status_code} to {p.netloc}{p.path}")
            login_page = session.get(next_url, allow_redirects=False, timeout=30)

        print(f"  Landed on: {urlparse(login_page.url).path}")

        login_resp = try_new_ul_login(session, auth0_base, login_page, email, password)
        if login_resp is None:
            die("All login strategies failed")

        print("\n[3] Following callback chain → Hermes...")
        final_resp = follow_full_chain(session, login_resp)

    # Collect cookies
    cookies = collect_target_cookies(session)

    if not cookies:
        print(f"\nChain ended at: {final_resp.url}", file=sys.stderr)
        print(f"Final status: {final_resp.status_code}", file=sys.stderr)
        print("\nAll cookies:", file=sys.stderr)
        for c in session.cookies:
            print(f"  [{c.domain}] {c.name} = {c.value[:50]}...", file=sys.stderr)
        die("Target cookies not found.")

    print()
    for name in TARGET_COOKIES:
        if name in cookies:
            print(f"{name}: {cookies[name]}")
        else:
            print(f"{name}: (not found)")


if __name__ == "__main__":
    main()
