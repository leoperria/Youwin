
SELECT 
 gp.ID_premio, p.codice,count(ID_giornata) as cnt, 
 sum(qnt_massimale) as qnt_massimale, sum(qnt_vinta) as qnt_vinta 
FROM giornate_premi gp 
LEFT JOIN giornate g ON gp.ID_giornata=g.ID 
LEFT JOIN premi p ON gp.ID_premio=p.ID 
WHERE gp.ID_concorso=1 AND data <="2009-05-30" GROUP BY gp.ID_premio