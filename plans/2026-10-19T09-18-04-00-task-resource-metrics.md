# Record per-command resource usage

## Goal

Measure CPU and peak memory of each scheduled task to tune OpenShift resource requests,
and to judge whether the scheduler could run in the app pod instead of a CronJob.

## Approach

- `App\Listeners\RecordCommandResourceUsage` subscribes to `CommandStarting` /
  `CommandFinished` and, for the outermost artisan command of a process, inserts a row into
  `command_resource_usages` with wall time, user/system CPU, `ru_maxrss`, child-process
  CPU/max RSS, PHP peak memory, exit code, and host.
- Nested `Artisan::call()` commands (e.g. the `->after()` hooks on `omim:update-data`) share
  the process, so they are attributed to the outer command (`schedule:run`).
- The listener is a container singleton because subscribers are otherwise re-resolved per
  event, losing nesting state.
- Stored in the database, not logs: the scheduler sends child output to `/dev/null` and
  CronJob pods are ephemeral.
- Failures are reported, never thrown, so metrics cannot change a command's exit status.
- Toggle with `RECORD_COMMAND_RESOURCE_USAGE` (`logging.record_command_resource_usage`);
  disabled in `phpunit.xml`.

## Example query

    SELECT command, COUNT(*) runs, MAX(max_rss_kb)/1024 max_rss_mb,
           MAX(wall_seconds) max_wall_s, MAX(cpu_user_seconds + cpu_system_seconds) max_cpu_s
    FROM command_resource_usages GROUP BY command ORDER BY max_rss_mb DESC;
