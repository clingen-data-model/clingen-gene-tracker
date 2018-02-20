# ClinGen Curation Tracker

This project attempts to track curations being conducted by curators working for a Gene Clinicians Expert Panel (GCEP).

## Installation
```
git clone git@bitbucket.org:shepsweb/clingen.git
cd clingen
composer install
yarn install
cp .env.example .env # and updated your db settings etc.
php artisan key:generate
php artisan passport:keys  # generates keys for api auth. see https://laravel.com/docs/5.6/passport for details
```

## Terms
* **Gene** - A gene.  Usually represented by a [HGNC](https://www.genenames.org/) gene symbol.
* **Phenotype** - The set of observable characteristics of an individual resulting from the interaction of its genotype with the environment.
* **Disease Entity** - A disease of clinical interest.  May be composed of many phenotypes.
* **Curation Topic** - A gene & disease entity pair.
* **ExpertPanel** - AKA GCEP. A group of experts consulting on the curation of a curation topic.
* **WorkingGroup** - A group working on genetic disorders.  Has many ExpertPanels.

## Other Resources
* **[OMIM](https://www.omim.org/)** - Online Mendelian Inheritance in Man catalog of genes and genetic disorders
* **[MONDO](https://www.ebi.ac.uk/ols/ontologies/mondo)** - Monarch Disease Ontology: ontology of disease ontologies
* **[GCI](https://curation.clinicalgenome.org/)** - DB and interface for ClinGen Curators to manage classification of curation topics