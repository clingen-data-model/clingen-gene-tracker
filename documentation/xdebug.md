[Xdebug](https://xdebug.org) can be used to help with debugging of the PHP code.

The setup of the `app` container in the `docker-compose.yml` facilitates this with
some defaults (but they can be overridden in environment variables). Specifically,
running under docker-compose, xdebug will expect the client/IDE to be running on
the docker host (`host.docker.internal`) at the default port of 9003.

On the web browser side of things, you'll need something like the "Xdebug helper"
extension.

Your development environment will also need configuration-- both to listen for
xdebug connections and to map directories within the container to host paths. For
VSCode with the "PHP Debug" extension, the following snippet (within the
`configurations` block of `launch.json`)

```
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "pathMappings": {
                "/srv/app": "${workspaceRoot}"
            },
            "hostname": "0.0.0.0",
            "port": 9003
        },
```

To debug `php artisan` commands with Xdebug, you may additionally need to
`export XDEBUG_SESSION=1` in the shell before running the command.
