INSERT INTO module_course (pai_module_id, course_id, curriculum, prodi, created_at, updated_at)
SELECT m.id, c.id, data.curriculum, data.prodi, NOW(), NOW()
FROM (
  SELECT 'A10' AS module_code, 'MAA62043' AS course_code, 'baru' AS curriculum, 'S1 Ilmu Aktuaria' AS prodi UNION ALL
  SELECT 'A10', 'MAA61041', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A10', 'MAA62009', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A10', 'MAA61015', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A20', 'MAA62003', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A20', 'MAA61007', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A20', 'MAA62003', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A20', 'MAA61007', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A20', 'MAM60601', 'baru', 'S1 Matematika' UNION ALL
  SELECT 'A20', 'MAM60602', 'baru', 'S1 Matematika' UNION ALL
  SELECT 'A30', 'MAA62004', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A30', 'MAA61052', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A30', 'MAA62004', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A30', 'MAA61009', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A40', 'MAA62042', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A40', 'MAA61044', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A40', 'MAA62007', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A40', 'MAA61022', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA62045', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA61016', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA62047', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA62045', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA61016', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A50', 'MAA62047', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A60', 'MAA62048', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A60', 'MAA61033', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A60', 'MAA62028', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A60', 'MAA61033', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A70', 'MAA62044', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A70', 'MAA61051', 'baru', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A70', 'MAA62008', 'lama', 'S1 Ilmu Aktuaria' UNION ALL
  SELECT 'A70', 'MAA61035', 'lama', 'S1 Ilmu Aktuaria'
) AS data
JOIN pai_modules m ON m.code = data.module_code
JOIN courses c ON c.code = data.course_code;
