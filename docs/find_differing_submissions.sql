-- Query to find submissions where submitted_as_ values differ from normalized table references
SELECT 
    s.id,
    s.friendly,
    s.status,
    
    -- Gene differences
    CASE WHEN s.submitted_as_hgnc_id != g.curie THEN 'GENE_ID_DIFFERS' ELSE NULL END as gene_id_diff,
    CASE WHEN s.submitted_as_hgnc_symbol != g.title THEN 'GENE_SYMBOL_DIFFERS' ELSE NULL END as gene_symbol_diff,
    s.submitted_as_hgnc_id as submitted_gene_id,
    g.curie as normalized_gene_id,
    s.submitted_as_hgnc_symbol as submitted_gene_symbol,
    g.title as normalized_gene_symbol,
    
    -- Disease differences  
    CASE WHEN s.submitted_as_disease_id != d.curie THEN 'DISEASE_ID_DIFFERS' ELSE NULL END as disease_id_diff,
    CASE WHEN s.submitted_as_disease_name != d.title THEN 'DISEASE_NAME_DIFFERS' ELSE NULL END as disease_name_diff,
    s.submitted_as_disease_id as submitted_disease_id,
    d.curie as normalized_disease_id,
    s.submitted_as_disease_name as submitted_disease_name,
    d.title as normalized_disease_name,
    
    -- Mode of Inheritance differences
    CASE WHEN s.submitted_as_moi_id != i.curie THEN 'MOI_ID_DIFFERS' ELSE NULL END as moi_id_diff,
    CASE WHEN s.submitted_as_moi_name != i.title THEN 'MOI_NAME_DIFFERS' ELSE NULL END as moi_name_diff,
    s.submitted_as_moi_id as submitted_moi_id,
    i.curie as normalized_moi_id,
    s.submitted_as_moi_name as submitted_moi_name,
    i.title as normalized_moi_name,
    
    -- Classification differences
    CASE WHEN s.submitted_as_classification_id != c.curie THEN 'CLASSIFICATION_ID_DIFFERS' ELSE NULL END as classification_id_diff,
    CASE WHEN s.submitted_as_classification_name != c.title THEN 'CLASSIFICATION_NAME_DIFFERS' ELSE NULL END as classification_name_diff,
    s.submitted_as_classification_id as submitted_classification_id,
    c.curie as normalized_classification_id,
    s.submitted_as_classification_name as submitted_classification_name,
    c.title as normalized_classification_name,
    
    -- Submitter info for context
    sub.title as submitter_name,
    s.created_at,
    s.updated_at

FROM submissions s
LEFT JOIN genes g ON s.gene_id = g.id
LEFT JOIN diseases d ON s.disease_id = d.id  
LEFT JOIN inheritances i ON s.moi_id = i.id
LEFT JOIN classifications c ON s.classification_id = c.id
LEFT JOIN submitters sub ON s.submitter_id = sub.id

WHERE 
    -- Only show records where at least one submitted_as_ value differs
    (
        s.submitted_as_hgnc_id != g.curie OR
        s.submitted_as_hgnc_symbol != g.title OR
        s.submitted_as_disease_id != d.curie OR
        s.submitted_as_disease_name != d.title OR
        s.submitted_as_moi_id != i.curie OR
        s.submitted_as_moi_name != i.title OR
        s.submitted_as_classification_id != c.curie OR
        s.submitted_as_classification_name != c.title
    )
    AND s.status = 1  -- Only published submissions

ORDER BY s.submitter_id, s.created_at DESC;