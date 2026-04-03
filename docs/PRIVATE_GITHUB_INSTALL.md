# Private GitHub Composer Install

This guide explains how to host MVC Forge in a private GitHub repository and install it from another project or another domain/server.

## Recommended Repository Shape

Keep the full framework/application repository private, tag releases with SemVer, and reference the repository as a VCS source from the consumer project.

Example tag:

```bash
git tag v0.1.0
git push origin v0.1.0
```

## Option 1: SSH Deploy Key

Use this when a server should have read-only access to one private repository.

On the target server:

```bash
ssh-keygen -t ed25519 -C "mvc-forge-deploy" -f ~/.ssh/mvc_forge_deploy
cat ~/.ssh/mvc_forge_deploy.pub
```

Add that public key in GitHub repository settings as a Deploy Key with read-only access.

Configure SSH host mapping:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com-mvc-forge
  HostName github.com
  User git
  IdentityFile ~/.ssh/mvc_forge_deploy
  IdentitiesOnly yes
EOF
```

Consumer project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com-mvc-forge:<github-user-or-org>/<private-repo>.git"
    }
  ],
  "require": {
    "mvc-forge/framework": "^0.1"
  }
}
```

Then install:

```bash
composer require mvc-forge/framework:^0.1
```

## Option 2: GitHub Personal Access Token

Use this when SSH deploy keys are not practical and the environment can safely store a machine token.

```bash
composer config --global --auth github-oauth.github.com <github-token>
```

Consumer project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/<github-user-or-org>/<private-repo>.git"
    }
  ],
  "require": {
    "mvc-forge/framework": "^0.1"
  }
}
```

## Important Notes

- For private Composer installation, GitHub repository access must be configured before `composer require`.
- Keep release tags aligned with SemVer, for example `v0.1.0`, `v0.2.0`, `v1.0.0`.
- If the repository should stay non-public, do not publish the package to public Packagist.
- The current package name is `mvc-forge/framework`; change it only with a planned migration if consumers already depend on it.
