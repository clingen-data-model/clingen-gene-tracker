# Refreshing current prod data into the demo instance

This is not done in an automated fashion because we sometimes want to let the demo
instance "drift". The main use case for this is when a new admin or coordinator
is being trained, so they can work through processes and not have things get reset.

However, it is also useful to update the demo data every so often, for instance to
get the demo in the current state of prod to test a specific issue. For this reason,
there is a k8s CronJob, `refresh-demo-data` which is loaded on the cluster but in
"suspend" state.

Ideally, you would juse be able to use `kubectl create job` with the `--from` option
to set this going as a one-off, but as of Oct 2024, there is bug with the way certain
permissions are set up when you try this, so you need to remove mention of "ownerReferences"
in the Job resource that is created.

The easiest way I've figured out to kludge around this uses [jq](https://jqlang.github.io/jq/)
to process the resource definition before applying, like this:

```
kubectl create job --from=cronjob/refresh-demo-data refresh-demo-data-$(date '+%Y%m%d-%H%M') --dry-run=client -o json | jq '.metadata |= (del(.ownerReferences))' | kubectl apply -f -
```
