# TODOS

- vscode [devcontainers](https://code.visualstudio.com/docs/devcontainers/containers)
  (not mac-specific)
- evaluate [colima](https://github.com/abiosoft/colima) vs Docker Desktop
- for M1 macs (arm64 arch), evaluate using native containers vs. x86_64)
- explain some of the rationale for this setup
- figure out if the case-sensitivity part below is really important (using a named volume
  for mysql data may obviate this)
- ensure compatibility with podman/podman desktop?
- switch to kubernetes-based dev environment (e.g., with minikube or k3s)
  to make more similar to production? 

# Setup some general things (not per-project, should just need to do once)

## DOCKER_USER variable

In order to run containers as the same uid/gid as the current user (so docker local mounts
have the appropriate permissions), we will use a convention that DOCKER_USER will be set to
`${UID}:${GID}`. UID and GID are shell variables, not environment variables (for reasons I
don't understand), so we have to set this up in a `.profile`, `.zprofile` or the likes.
I just put `export DOCKER_USER=${UID}:${GID}` at the beginning of my `~/.zprofile`.
If your shell doesn't define $GID, you can use something like `$(id -u):$(id -g)`.

## Case sensitivity for src

**It's not clear if this is important or not, especially with mysql's storage being in
a docker volume instead of a bind mount**

The current default filesystem on macos can be set to be case sensitive or not. By default
it is case insensitive, and apparently some software relies on this (like some Adobe stuff,
maybe). Yet case sensitivity is expected for other software (for instance mysql...)

MacOs allows for creation of "subvolumes" that are case sensitive. I put one of these
under `$HOME/src` and put all of my source code there. For example:

```bash
sudo diskutil apfs addVolume disk1 APFSX src -mountpoint "$HOME/src"
sudo chown $DOCKER_USER "$HOME/src"
```

# Install [homebrew](brew.sh)

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

(echo; echo 'eval "$(/usr/local/bin/brew shellenv)"') >> "$HOME/.zprofile"
    eval "$(/usr/local/bin/brew shellenv)"
```

# Install brew packages

```bash
# dev environment
brew install colima docker docker-compose
# other useful tools
brew install git git-crypt kubernetes-cli jq
# some personal preferences (bpow)
brew install iterm2 neovim
```

# Initial colima setup

It's important to set the `vm-type` and `mount-type` on iniital run of colima so that
the more-efficient virtiofs mounting method is used (also avoids some race conditions
that were present in sshfs-based mounting). Subsequent runs of `colima start` (e.g.,
after restarting) should use the settings established here.

```bash
colima start --vm-type vz --mount-type virtiofs --cpu 6 --memory 6 --disk 90
```

