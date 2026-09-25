{
  lib,
  ...
}:
{
  # x86_64-darwin is not supported any longer on NixOS 26.11
  # See: https://nixos.org/manual/nixpkgs/unstable/release-notes#x86_64-darwin-26.11
  systems = lib.filter (system: system != "x86_64-darwin") lib.systems.flakeExposed;
}
