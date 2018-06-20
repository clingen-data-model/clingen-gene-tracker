<style>
    .criteria-table {
        font-size: .8em;
    }
</style>
<template>
    <div class="criteria-table" style="line-height: 1">
        <table class="table table-bordered">
            <thead>
                <tr><th>Criteria</th><th>Question</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Assertion</td>
                    <td><small class="short-line-height">Have assertions for more than one disease entity been reported for the gene of interest?</small></td>
                </tr>
                <tr>
                    <td>Molecular mechanism</td>
                    <td><small class="short-line-height">Are there differences in molecular mechanisms between the disease entities associated with the gene?</small></td>
                </tr>
                <tr>
                    <td>Phenotypic variability</td>
                    <td><small class="short-line-height">Have examples of intrafamilial and/or interfamilial phenotypic variability been reported for the disease entities associated with the gene in question?</small></td>
                </tr>
                <tr>
                    <td>Inheritance Pattern</td>
                    <td><small class="short-line-height">Do the disease entities associated with different inheritance patterns represent a continuum of disease or distinct disease entities?</small></td>
                </tr>
            </tbody>
        </table>    
        <a 
            href="https://www.clinicalgenome.org/site/assets/files/9703/lumping_and_splitting_guidelines_gene_curation_final.pdf" 
            target="criteria"
            style="font-size: .95rem"
        >
            Lumping and splitting criteria overview
        </a>
    </div>
</template>
<script>
    export default {
        //component definition
    }
</script>