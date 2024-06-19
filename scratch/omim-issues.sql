select * from phenotypes where omim_status in ('moved', 'removed') or deleted_at is not null;

select distinct omim_status from phenotypes;

select * from curation_phenotype;

select distinct revisionable_type from revisions;

select 
	p.id as `ph_id`,
	p.mim_number, 
	p.name, 
	p.created_at as `ph_cr`, 
	p.updated_at as `ph_up`,
	g.gene_symbol,
	'|' as `|`,
	gp.created_at as `gp_cr`, 
	gp.updated_at as `gp_up` 
from phenotypes p 
 join gene_phenotype gp on p.id = gp.phenotype_id
 join genes g on gp.hgnc_id = g.hgnc_id
where mim_number in (254780, 276300);


select * from revisions where revisionable_type = 'App\\Phenotype' and revisionable_id = 596;

# Curations with a phenotype that's actually a gene
select 
	c.id as `curation_id`, 
	p.id as `phenotype_id`, 
	p.mim_number as `mim_number`, 
	p.name as `phenotype_name`,
	p.created_at as `pheno_created`,
	p.updated_at as `pheno_updated`,
	CONCAT('https://omim.org/entry/', p.mim_number) as `omim_entry`,
	'|' as `|`,
	g.hgnc_id as `hgnc_id`,
	g.gene_symbol as `gene_symbol`
from genes g
	join gene_phenotype gp on g.hgnc_id = gp.hgnc_id
	left join phenotypes p on gp.phenotype_id = p.id
-- 	left join curation_phenotype cp on p.id = cp.phenotype_id
-- 	left join curations c on cp.curation_id = c.id
-- where exists (select * from phenotypes where mim_number in (select omim_id from genes) and phenotypes.id = cp.phenotype_id)
where mim_number in (select omim_id from genes)
order by p.mim_number;

select * from revisions where revisionable_type='App\\Phenotype' and revisionable_id in (
select distinct p.id from curations c 
	join curation_phenotype cp on c.id = cp.curation_id
	join phenotypes p on cp.phenotype_id = p.id
where exists (select * from phenotypes where mim_number in (select omim_id from genes) and phenotypes.id = cp.phenotype_id)
);



select
	CONCAT('https://gene-tracker.clinicalgenome.org/home#/curations/', c.id) as `curation`, 
	' ' as ` `,
	p.id as `phenotype_id`, 
	p.mim_number as `phenotype_mim_number`, 
	p.name as `phenotype_name`,
	p.created_at as `pheno_created`,
	p.updated_at as `pheno_updated`,
	CONCAT('https://omim.org/entry/', p.mim_number) as `omim_entry`,
	' ' as ` `,
	g.hgnc_id as `hgnc_id`,
	g.omim_id as `gene_mim_number`,
	g.gene_symbol as `gene_symbol`
from phenotypes p
	left join gene_phenotype gp on p.id = gp.phenotype_id
	left join genes g on g.omim_id = p.mim_number
	left join curation_phenotype cp on cp.phenotype_id = p.id
	left join curations c on cp.curation_id = c.id
where mim_number in (select omim_id from genes) 
order by mim_number;




select * from genes where omim_id in (select mim_number from phenotypes) order by omim_id;

select * from phenotypes where mim_number = 614156;



