# Change log

## 2020-08-21 - Gracefully handle HGNC API server errors.
* Prevent error response when new curation created (or gene symbol changed) if HGNC API responds with 500 error on lookup.