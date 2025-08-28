-- Summary count of submissions with differing submitted_as_ values
SELECT 
    'Gene ID' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN genes g ON s.gene_id = g.id
WHERE s.submitted_as_hgnc_id != g.curie AND s.status = 1

UNION ALL

SELECT 
    'Gene Symbol' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN genes g ON s.gene_id = g.id
WHERE s.submitted_as_hgnc_symbol != g.title AND s.status = 1

UNION ALL

SELECT 
    'Disease ID' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN diseases d ON s.disease_id = d.id
WHERE s.submitted_as_disease_id != d.curie AND s.status = 1

UNION ALL

SELECT 
    'Disease Name' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN diseases d ON s.disease_id = d.id
WHERE s.submitted_as_disease_name != d.title AND s.status = 1

UNION ALL

SELECT 
    'MOI ID' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN inheritances i ON s.moi_id = i.id
WHERE s.submitted_as_moi_id != i.curie AND s.status = 1

UNION ALL

SELECT 
    'MOI Name' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN inheritances i ON s.moi_id = i.id
WHERE s.submitted_as_moi_name != i.title AND s.status = 1

UNION ALL

SELECT 
    'Classification ID' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN classifications c ON s.classification_id = c.id
WHERE s.submitted_as_classification_id != c.curie AND s.status = 1

UNION ALL

SELECT 
    'Classification Name' as field_type,
    COUNT(*) as differing_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM submissions WHERE status = 1), 2) as percentage
FROM submissions s
LEFT JOIN classifications c ON s.classification_id = c.id
WHERE s.submitted_as_classification_name != c.title AND s.status = 1

ORDER BY differing_count DESC;