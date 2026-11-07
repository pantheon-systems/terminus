# Cookbook

Methods for common needs

## Remove a user from all sites within an organization

This can be accomplished with a shell one-liner:

```bash
for SITE in $(terminus org:site:list $ORG --format=string --field=name); do
  terminus site:team:remove $SITE $EMAIL
done
```

In the snippet, `SITE` is being declared implicitly as part of the loop, but `$ORG` and `$EMAIL` need to be defined as environment variables, or replaced with the relevant values.

