{ php, lib, ... }:
let
  fs = lib.fileset;
  sourceFiles = ../../../.;
in
php.buildComposerProject2 {
  pname = "terminus-dev";
  version = "4.3.3-dev";

  composerNoDev = false;

  src = fs.toSource {
    root = sourceFiles;
    fileset = sourceFiles;
  };

  vendorHash = "sha256-JdphAOzISFlRupU2ZjlFpzv/4uox7ASZHl0lnieL/Ek=";

  meta = {
    mainProgram = "terminus";
  };
}
