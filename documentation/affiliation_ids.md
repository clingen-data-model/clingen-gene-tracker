# History

In days of old, affiliation ids (shared amongst GCI/VCI and then to other ClinGen systems)
were managed in a google docs sheet. Determining the potential failure modes is left as
an exercise to the creative reader.

"10000"-series ids are used for general groups, with "40000" and "50000" series for gene
curation expert panels and variant curation expert panels, respectively. Historically,
there could be one 10000-series that encompassed both a paired set of 40000 and 50000
series groups. That caused problems when one wanted to change its name and not the other.

Going forward, it is likely that each VCEP or GCEP will have it's own "parent group",
in other words, that a parent group will have at most one subgroup.

# Affiliations microservice v1 2025

The [affiliations service](https://github.com/ClinGen/stanford-affils) is developed by the
Stanford GCI/VCI team. They are the ones to contact if there are new feature requests
or need for additional access.

Relevant urls are `https://affils-test.clinicalgenome.org/api/affiliations_list/` for
test or `https://affils.clinicalgenome.org/api/affiliations_list/` for production.

These should be used in `AFFILIATIONS_API_URL` and `AFFILIATIONS_API_KEY` should also
be provided in the environment to match.

Currently these affiliation ids are updated every 6 hours in the `affiliations:update-data`
scheduled task.

