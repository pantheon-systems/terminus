{
  perSystem =
    { pkgs, config, ... }:
    {
      # Dev shells: nix develop
      # nix develop
      devShells.default = pkgs.mkShellNoCC {
        packages = with pkgs; [
          php
          git
          phpPackages.box
          phpPackages.composer
          openssh
          config.packages.terminus 
        ];
      };
    };
}
