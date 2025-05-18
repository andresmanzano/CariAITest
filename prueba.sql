SELECT SUM(Ord.Quantity) as Total_orders

FROM Orders Ord

WHERE 1


-------------------------------------------------------
SELECT Pro.Name as Producto,Cli.Name as Cliente, Ord.Quantity as Cantidad, Ord.Total

FROM Orders Ord
INNER JOIN Product Pro ON Pro.Productid = Ord.Productid
INNER JOIN Client Cli ON Cli.Clientid = Ord.Clientid

WHERE Pro.Productid = 1

----------------------------
SELECT Pro.Name as Producto, Pro.Reference as Referencia, SUM(Ord.Quantity) as Cantidad, SUM(Ord.Total) as Total
FROM Orders Ord
INNER JOIN Product Pro ON Pro.Productid = Ord.Productid
WHERE 1
GROUP BY Ord.Productid
---------------------

SELECT Cli.Name as Nombre, Cli.LastName as Apellido, SUM(Ord.Total) Total

FROM Orders Ord
INNER JOIN Client Cli ON Cli.Clientid = Ord.Clientid

WHERE 1

GROUP BY Cli.Clientid
HAVING SUM(Ord.Total) > 10000000