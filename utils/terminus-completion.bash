# bash completion for the `terminus` command

_terminus_complete() {
  local cur=${COMP_WORDS[COMP_CWORD]}

  IFS=$'\n';  # want to preserve spaces at the end
  local opts=( $(terminus cli completions --line="$COMP_LINE" --point="$COMP_POINT") )

  if [[ $opts = "<file>" ]]
  then
    COMPREPLY=( $(compgen -f -- $cur) )
  else
    COMPREPLY=$opts
  fi
}
complete -o nospace -F _terminus_complete terminus
