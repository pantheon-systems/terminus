# Documentation: https://docs.brew.sh/Formula-Cookbook
#                https://rubydoc.brew.sh/Formula
# PLEASE REMOVE ALL GENERATED COMMENTS BEFORE SUBMITTING YOUR PULL REQUEST!
class Terminus < Formula
  desc "The Pantheon CLI — a standalone utility for performing operations on the Pantheon Platform"
  homepage "https://pantheon.io"
  url "https://github.com/pantheon-systems/terminus/archive/refs/tags/2.6.0.tar.gz"
  sha256 "4bc55b913751527efba54443d2f7055d8902ca66d3c63a9399035c6fa21c980e"
  license "NOASSERTION"

  depends_on "php@7.4"
  depends_on "composer"

  def install
    system "composer install"
    system "composer phar:install-tools"
    system "[ -f /usr/local/bin/terminus ] && mv /usr/local/bin/terminus /usr/local/bin/terminus-old"
    system "ln -s ./terminus.phar /usr/local/bin/terminus"
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
