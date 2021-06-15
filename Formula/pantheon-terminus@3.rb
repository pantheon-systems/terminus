# Documentation: https://docs.brew.sh/Formula-Cookbook
#                https://rubydoc.brew.sh/Formula
# PLEASE REMOVE ALL GENERATED COMMENTS BEFORE SUBMITTING YOUR PULL REQUEST!
class PantheonTerminusAt3 < Formula
  desc "The Pantheon CLI (v3.0) — a standalone utility for performing operations on the Pantheon Platform"
  homepage "https://pantheon.io"
  url "https://github.com/pantheon-systems/terminus/archive/refs/tags/3.0.0-alpha2.tar.gz"
  sha256 "d04d45f3c0c4ce59720723f1abbbe013aafcf82391cf2f149594b45cf317c90f"
  license "NOASSERTION"

  depends_on "php@7.4"
  depends_on "composer"

  def install
    system "composer install"
    system "composer phar:build"
    system "composer phar:install"
  end

  test do
    # `test do` will create, run in and delete a temporary directory.
    #
    # This test will fail and we won't accept that! For Homebrew/homebrew-core
    # this will need to be a test that verifies the functionality of the
    # software. Run the test with `brew test terminus`. Options passed
    # to `brew install` such as `--HEAD` also need to be provided to `brew test`.
    #
    # The installed folder is not in the path, so use the entire path to any
    # executables being tested: `system "#{bin}/program", "do", "something"`.
    system "false"
  end
end
