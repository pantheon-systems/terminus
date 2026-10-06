# A simple shell.nix to facilitate development.
# Use `nix-shell --arg withDev true` to build and run terminus with dev dependencies.
{ withDev ? false, ... }:

let
  nixpkgs = fetchTarball "https://github.com/NixOS/nixpkgs/tarball/nixos-26.05";
  pkgs = import nixpkgs { config = {}; overlays = []; };
  terminus = if withDev == true then pkgs.callPackage ./nix/pkgs/terminus-dev/package.nix { inherit pkgs withDev; } else pkgs.callPackage ./nix/pkgs/terminus/package.nix { inherit pkgs withDev;  };
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
