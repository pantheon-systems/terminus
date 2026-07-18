# A simple shell.nix to facilitate development.
# Use `nix-shell --arg dev true` to build and run terminus with dev dependencies.
{ dev ? false, ... }:

let
  nixpkgs = fetchTarball "https://github.com/NixOS/nixpkgs/tarball/nixos-26.05";
  pkgs = import nixpkgs { config = {}; overlays = []; };
  terminus = if dev == true then pkgs.callPackage ./nix/package-dev.nix { inherit pkgs; } else pkgs.callPackage ./nix/package.nix { inherit pkgs; };
in {

 shell = (pkgs.mkShellNoCC {
  packages = with pkgs; [ 
    php 
    phpPackages.box 
    phpPackages.composer 
    git
    openssh
    terminus
  ];
  });
}
