# SSL certificate update
SSL certificates need to be updated from time to time.

Certificates for the clinicalgenome.org domain and it's subdomains are handled by the **Bryan Wulf** of the Stanford team.

The current certificate should expire in September 2023.

1. Update cert, ca-cert, and private key in 
    * `dept-genetracker` - route/gene-tracker.clinicalgenome.org
    * `dept-communitycuration` - route/ccdb.clinicalgenome.org, 
    * `dept-gpm` - route/gpm.clinicalgenome.org
1. Email cloudapps@unc.edu about new certificate so they can add it to their load balancer:
    > Hi folks,
    > I've just updated the SSL certs for the following urls: 
    > * gene-tracker.clinicalgenome.org
    > * ccdb.clinicalgenome.org
    > * gpm.clinicalgenome.org
    > 
    > My onyen is jward3
    >
    > Thanks!
    >
    > TJ Ward

### NOTE: 
Password protected keys are **not** supported by OpenShift. If you are given a password protected private key you can convert it to a standard RSA:
```
openssl rsa -in pw-protected-private.key -out tls.key
```
