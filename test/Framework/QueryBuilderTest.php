<?php

namespace Test\Framework;

use Framework\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private $queryBuilder;

    public function setUp(): void
    {
        $this->queryBuilder = new QueryBuilder();
    }

    public function testInsertInto()
    {
        $this->queryBuilder
            ->executeMode()
            ->insertInto('produit')
            ->setParams([
                'produit_nom' => 'nom',
                'produit_prix' => 1.78,
                'produit_description' => 'description'
            ]);

        $this->assertEquals(
            "INSERT INTO produit (produit_nom, produit_prix, produit_description) VALUES ('nom', 1.78, 'description');",
            $this->queryBuilder->toSQL()
        );
    }

    public function testPreparedInsertInto()
    {
        $this->queryBuilder
            ->insertInto('produit')
            ->setParams([
                'produit_nom' => 'nom',
                'produit_prix' => 1.78,
                'produit_description' => 'description'
            ]);

        $this->assertEquals(
            "INSERT INTO produit (produit_nom, produit_prix, produit_description) VALUES (:produit_nom, :produit_prix, :produit_description);",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();

        $this->assertArrayHasKey('produit_nom', $binds);
        $this->assertArrayHasKey('produit_prix', $binds);
        $this->assertArrayHasKey('produit_description', $binds);

        $this->assertStringContainsString('nom', $binds['produit_nom']);
        $this->assertEquals(1.78, $binds['produit_prix']);
        $this->assertStringContainsString('description', $binds['produit_description']);
    }

    public function testInsertIntoWithPrefix()
    {
        $this->queryBuilder
            ->executeMode()
            ->insertInto('produit')
            ->setPrefix('pdt_')
            ->setParams([
                'produit_nom' => 'nom',
                'produit_prix' => 1.78,
                'produit_description' => 'description'
            ]);

        $this->assertEquals(
            "INSERT INTO produit (pdt_produit_nom, pdt_produit_prix, pdt_produit_description) VALUES ('nom', 1.78, 'description');",
            $this->queryBuilder->toSQL()
        );
    }

    public function testPreparedInsertIntoWithPrefix()
    {
        $this->queryBuilder
            ->insertInto('produit')
            ->setPrefix('pdt_')
            ->setParams([
                'produit_nom' => 'nom',
                'produit_prix' => 1.78,
                'produit_description' => 'description'
            ]);

        $this->assertEquals(
            "INSERT INTO produit (pdt_produit_nom, pdt_produit_prix, pdt_produit_description) VALUES (:produit_nom, :produit_prix, :produit_description);",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();

        $this->assertArrayHasKey('produit_nom', $binds);
        $this->assertArrayHasKey('produit_prix', $binds);
        $this->assertArrayHasKey('produit_description', $binds);

        $this->assertStringContainsString('nom', $binds['produit_nom']);
        $this->assertEquals(1.78, $binds['produit_prix']);
        $this->assertStringContainsString('description', $binds['produit_description']);
    }

    public function testUpdate()
    {
        $this->queryBuilder
            ->executeMode()
            ->update('product')
            ->andWhere('id', 10)
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ]);

        $this->assertEquals(
            "UPDATE product SET name = 'produit', price = 2, desc = 'description' WHERE id = 10;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testUpdateWithPrefix()
    {
        $this->queryBuilder
            ->executeMode()
            ->update('product')
            ->andWhere('pdt_id', 10)
            ->setPrefix("pdt_")
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ]);

        $this->assertEquals(
            "UPDATE product SET pdt_name = 'produit', pdt_price = 2, pdt_desc = 'description' WHERE pdt_id = 10;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testPreparedUpdate()
    {
        $this->queryBuilder
            ->update('product')
            ->andWhere('id = :id', 10)
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ]);

        $this->assertEquals(
            "UPDATE product SET name = :name, price = :price, desc = :desc WHERE id = :id;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();

        $this->assertArrayHasKey('name', $binds);
        $this->assertArrayHasKey('price', $binds);
        $this->assertArrayHasKey('desc', $binds);
        $this->assertArrayHasKey('id', $binds);

        $this->assertStringContainsString('produit', $binds['name']);
        $this->assertEquals(2, $binds['price']);
        $this->assertStringContainsString('description', $binds['desc']);
        $this->assertEquals(10, $binds['id']);
    }

    public function testPreparedUpdateWithPrefix()
    {
        $this->queryBuilder
            ->update('product')
            ->setPrefix('pdt_')
            ->andWhere('pdt_id = :id', 10)
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ]);

        $this->assertEquals(
            "UPDATE product SET pdt_name = :name, pdt_price = :price, pdt_desc = :desc WHERE pdt_id = :id;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();

        $this->assertArrayHasKey('name', $binds);
        $this->assertArrayHasKey('price', $binds);
        $this->assertArrayHasKey('desc', $binds);
        $this->assertArrayHasKey('id', $binds);

        $this->assertStringContainsString('produit', $binds['name']);
        $this->assertEquals(2, $binds['price']);
        $this->assertStringContainsString('description', $binds['desc']);
        $this->assertEquals(10, $binds['id']);
    }

    public function testUpdateWithMultiplesConditions()
    {
        $this->queryBuilder
            ->executeMode()
            ->update('product')
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ])
            ->andWhere('id', 10)
            ->andWhere("name", 'produit');

        $this->assertEquals(
            "UPDATE product SET name = 'produit', price = 2, desc = 'description' WHERE id = 10 AND name = 'produit';",
            $this->queryBuilder->toSQL()
        );
    }

    public function testPreparedUpdateWithMultiplesConditions()
    {
        $this->queryBuilder
            ->update('product')
            ->setParams([
                'name' => 'produit',
                'price' => 2.0,
                'desc' => 'description'
            ])
            ->andWhere('id = :id', 10)
            ->andWhere("product = :nom_produit", 'produit10');

        $this->assertEquals(
            "UPDATE product SET name = :name, price = :price, desc = :desc WHERE id = :id AND product = :nom_produit;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();

        $this->assertArrayHasKey('name', $binds);
        $this->assertArrayHasKey('price', $binds);
        $this->assertArrayHasKey('desc', $binds);
        $this->assertArrayHasKey('id', $binds);
        $this->assertArrayHasKey('nom_produit', $binds);

        $this->assertStringContainsString('produit', $binds['name']);
        $this->assertEquals(2, $binds['price']);
        $this->assertStringContainsString('description', $binds['desc']);
        $this->assertEquals(10, $binds['id']);
        $this->assertEquals('produit10', $binds['nom_produit']);
    }

    public function testUpdateWithInnerJoin()
    {
        $this->queryBuilder
            ->update('product p')
            ->setParams([
                'desc' => 'description'
            ])
            ->andWhere('c.category_nom = :category_nom', 'masque')
            ->andWhere('p.product_nom = :nom_produit', 'jouet')
            ->innerJoin('category c', 'c.category_id = p.product_id');

        $this->assertEquals(
            "UPDATE product p INNER JOIN category c ON c.category_id = p.product_id SET desc = :desc WHERE c.category_nom = :category_nom AND p.product_nom = :nom_produit;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testUpdateWithLeftJoin()
    {
        $this->queryBuilder
            ->update('product p')
            ->setParams([
                'desc' => 'description'
            ])
            ->andWhere('c.category_nom = :category_nom', 'masque')
            ->andWhere('p.product_nom = :nom_produit', 'jouet')
            ->leftJoin('category c', 'c.category_id = p.product_id');

        $this->assertEquals(
            "UPDATE product p LEFT JOIN category c ON c.category_id = p.product_id SET desc = :desc WHERE c.category_nom = :category_nom AND p.product_nom = :nom_produit;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testUpdateWithMultiplesInnerJoin()
    {
        $this->queryBuilder
            ->update('product p')
            ->setParams([
                'desc' => 'description'
            ])
            ->andWhere('c.category_nom = :category_nom', 'masque')
            ->innerJoin('category c', 'c.category_id = p.product_id')
            ->andWhere('p.product_nom = :nom_produit', 'jouet')
            ->innerJoin('producteur pr', 'pr.producteur_id = p.producteur_id');

        $this->assertEquals(
            "UPDATE product p INNER JOIN category c ON c.category_id = p.product_id INNER JOIN producteur pr ON pr.producteur_id = p.producteur_id SET desc = :desc WHERE c.category_nom = :category_nom AND p.product_nom = :nom_produit;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSimpleDelete()
    {
        $this->queryBuilder
            ->executeMode()
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit', 'bic');

        $this->assertEquals(
            "DELETE FROM product p WHERE p.nom_produit = 'bic';",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSimpleDeleteWithJoin()
    {
        $this->queryBuilder
            ->executeMode()
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit', 'bic');

        $this->assertEquals(
            "DELETE FROM product p WHERE p.nom_produit = 'bic';",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSimpleDeleteWithMultipleConditions()
    {
        $this->queryBuilder
            ->executeMode()
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit', 'bic')
            ->andWhere('p.product_id', 10);

        $this->assertEquals(
            "DELETE FROM product p WHERE p.nom_produit = 'bic' AND p.product_id = 10;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testPreparedDelete()
    {
        $this->queryBuilder
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit = :nom_produit', 'bic');

        $this->assertEquals(
            "DELETE FROM product p WHERE p.nom_produit = :nom_produit;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();
        $this->assertArrayHasKey('nom_produit', $binds);
        $this->assertEquals('bic', $binds['nom_produit']);
    }

    public function testPreparedDeleteWithMultiplesConditions()
    {
        $this->queryBuilder
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit = :nom_produit', 'bic')
            ->andWhere('p.category_id = :category_id', 10);

        $this->assertEquals(
            "DELETE FROM product p WHERE p.nom_produit = :nom_produit AND p.category_id = :category_id;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();
        $this->assertArrayHasKey('nom_produit', $binds);
        $this->assertEquals('bic', $binds['nom_produit']);
        $this->assertArrayHasKey('category_id', $binds);
        $this->assertEquals(10, $binds['category_id']);
    }

    public function testPreparedDeleteWithJoin()
    {
        $this->queryBuilder
            ->delete()
            ->from('product p')
            ->andWhere('p.nom_produit = :nom_produit', 'bic')
            ->andWhere('c.category_id = :category_id', 10)
            ->innerJoin('category c', 'c.category_id = p.category_id');

        $this->assertEquals(
            "DELETE FROM product p INNER JOIN category c ON c.category_id = p.category_id WHERE p.nom_produit = :nom_produit AND c.category_id = :category_id;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();
        $this->assertArrayHasKey('nom_produit', $binds);
        $this->assertEquals('bic', $binds['nom_produit']);
        $this->assertArrayHasKey('category_id', $binds);
        $this->assertEquals(10, $binds['category_id']);
    }

    public function testSimpleSelect()
    {
        $this->queryBuilder
            ->select()
            ->from("product");

        $this->assertEquals(
            "SELECT * FROM product;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSimpleSelectWithCondition()
    {
        $this->queryBuilder
            ->select()
            ->from("product")
            ->andWhere("product_nom = :nom", 'téléphonne');

        $this->assertEquals(
            "SELECT * FROM product WHERE product_nom = :nom;",
            $this->queryBuilder->toSQL()
        );

        $this->assertArrayHasKey('nom', $this->queryBuilder->getBindParams());
        $this->assertEquals('téléphonne', $this->queryBuilder->getBindParams()['nom']);
    }

    public function testSelectWithJoins()
    {
        $this->queryBuilder
            ->select()
            ->from("product p")
            ->innerJoin('category c', 'c.category_id = p.category_id');

        $this->assertEquals(
            "SELECT * FROM product p INNER JOIN category c ON c.category_id = p.category_id;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithJoinsAndConditions()
    {
        $this->queryBuilder
            ->select()
            ->from("product p")
            ->innerJoin('category c', 'c.category_id = p.category_id')
            ->andWhere('c.category_id = :category_id', 10)
            ->andWhere('p.product_nom = :product_nom', 'tablette');

        $this->assertEquals(
            "SELECT * FROM product p INNER JOIN category c ON c.category_id = p.category_id WHERE c.category_id = :category_id AND p.product_nom = :product_nom;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();
        $this->assertArrayHasKey('product_nom', $binds);
        $this->assertEquals('tablette', $binds['product_nom']);
        $this->assertArrayHasKey('category_id', $binds);
        $this->assertEquals(10, $binds['category_id']);
    }

    public function testSelectWithFields()
    {
        $this->queryBuilder
            ->select(null, 'champ1', 'champ2')
            ->from('produit');

        $this->assertEquals(
            "SELECT champ1, champ2 FROM produit;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithOrdrBy()
    {
        $this->queryBuilder
            ->select()
            ->from('produit')
            ->orderBy('ASC', 'produit1', 'produit2');

        $this->assertEquals(
            "SELECT * FROM produit ORDER BY produit1, produit2 ASC;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithMultipleOrdrBy()
    {
        $this->queryBuilder
            ->select()
            ->from('produit')
            ->orderBy('ASC', 'produit1', 'produit2')
            ->orderBy('DESC', 'produit3');

        $this->assertEquals(
            "SELECT * FROM produit ORDER BY produit1, produit2 ASC, produit3 DESC;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithGroupBy()
    {
        $this->queryBuilder
            ->select(null, 'COUNT(champ1)', 'champ2')
            ->from('produit')
            ->groupBy('champ1');

        $this->assertEquals(
            "SELECT COUNT(champ1), champ2 FROM produit GROUP BY champ1;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithGroupByAndOrderBy()
    {
        $this->queryBuilder
            ->select(null, 'COUNT(champ1)', 'champ2')
            ->from('produit')
            ->orderBy('ASC', 'champ2')
            ->groupBy('champ1');

        $this->assertEquals(
            "SELECT COUNT(champ1), champ2 FROM produit GROUP BY champ1 ORDER BY champ2 ASC;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testSelectWithGroupByAndMultipleOrderBy()
    {
        $this->queryBuilder
            ->select(null, 'COUNT(champ1)', 'champ2', 'champ3')
            ->from('produit')
            ->groupBy('champ1')
            ->orderBy('ASC', 'champ2')
            ->orderBy('DESC', 'champ3');

        $this->assertEquals(
            "SELECT COUNT(champ1), champ2, champ3 FROM produit GROUP BY champ1 ORDER BY champ2 ASC, champ3 DESC;",
            $this->queryBuilder->toSQL()
        );
    }

    public function testComplexSelect()
    {
        $this->queryBuilder
            ->select(null, 'COUNT(champ1) AS nb', 'champ2', 'champ3', "champ4")
            ->from('produit', 'p')
            ->innerJoin('category c', 'c.category_id = p.category_id')
            ->leftJoin('producteur pr', 'pr.producteur_id = p.producteur_id')
            ->andWhere('c.category_id = :category_id', 10)
            ->andWhere('p.produit_nom = :produit_nom', 'nomproduit')
            ->orderBy('ASC', 'champ2', 'champ3')
            ->orderBy('DESC', 'champ4')
            ->groupBy('champ2', 'champ3', 'champ4');

        $this->assertEquals(
            "SELECT COUNT(champ1) AS nb, champ2, champ3, champ4 FROM produit p INNER JOIN category c ON c.category_id = p.category_id " .
            "LEFT JOIN producteur pr ON pr.producteur_id = p.producteur_id WHERE c.category_id = :category_id AND p.produit_nom = :produit_nom " .
            "GROUP BY champ2, champ3, champ4 ORDER BY champ2, champ3 ASC, champ4 DESC;",
            $this->queryBuilder->toSQL()
        );

        $binds = $this->queryBuilder->getBindParams();
        $this->assertEquals(10, $binds['category_id']);
        $this->assertEquals('nomproduit', $binds['produit_nom']);
    }

    public function testMultipleSelect()
    {
        $this->queryBuilder
            ->select('t1', 'champ1 as c1', 'champ2 as c2')
            ->from('table1 t1')
            ->select('t2', 'champ3 as c3', 'champ4 as c4')
            ->innerJoin('table2 t2', 't1.colonne1 = t2.colonne2');

        $this->assertEquals(
            "SELECT t1.champ1 as c1, t1.champ2 as c2, t2.champ3 as c3, t2.champ4 as c4 FROM table1 t1 INNER JOIN table2 t2 ON t1.colonne1 = t2.colonne2;",
            $this->queryBuilder->toSQL()
        );
    }

	 public function testNoWhereOneInModeExecute() {
		 $this->queryBuilder
				->executeMode()
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->from('table1 t1')
				->in('t1.champ2', [1, 18]);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2 FROM table1 t1 WHERE t1.champ2 IN ('1','18');",
				$this->queryBuilder->toSQL()
		 );
	 }

	 public function testNoWhereMultiplesInModeExecute() {
		 $this->queryBuilder
				->executeMode()
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->from('table1 t1')
				->in('t1.champ1', [1, 10])
				->in('t1.champ2', [2, 20]);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2 FROM table1 t1 WHERE t1.champ1 IN ('1','10') AND t1.champ2 IN ('2','20');",
				$this->queryBuilder->toSQL()
		 );
	 }

	 public function testNoWhereMultiplesInModePreparedNoBindNames() {
		 $this->queryBuilder
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->from('table1 t1')
				->in('t1.champ1', [1, 10])
				->in('t1.champ2', [2, 20]);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2 FROM table1 t1 WHERE t1.champ1 IN ('1','10') AND t1.champ2 IN ('2','20');",
				$this->queryBuilder->toSQL()
		 );
	 }

	 public function testNoWhereMultiplesInModePrepared() {
		 $this->queryBuilder
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->from('table1 t1')
				->in('t1.champ1', [1, 10], ['val1', 'val2'])
				->in('t1.champ2', [2, 20], ['val3', 'val4']);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2 FROM table1 t1 WHERE t1.champ1 IN (:val1,:val2) AND t1.champ2 IN (:val3,:val4);",
				$this->queryBuilder->toSQL()
		 );
	 }


	 public function testNoWhereOneInModePrepared() {
		 $this->queryBuilder
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->from('table1 t1')
				->in('t1.champ1', [1, 10], ['val1', 'val2']);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2 FROM table1 t1 WHERE t1.champ1 IN (:val1,:val2);",
				$this->queryBuilder->toSQL()
		 );
	 }


	 public function testAndWhereOneInModeExecute() {
		 $this->queryBuilder
				->executeMode()
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->select('t2', 'colonne1 as col1', 'colonne2 as col2')
				->from('table1 t1')
				->innerJoin('table2 t2', 't1.champ1 = t2.colonne2')
				->andWhere('t2.colonne1', '400')
				->in('t2.colonne2', [1, 10], ['val1', 'val2']);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2, t2.colonne1 as col1, t2.colonne2 as col2 FROM table1 t1 INNER JOIN table2 t2 ON t1.champ1 = t2.colonne2 WHERE t2.colonne1 = '400' AND t2.colonne2 IN ('1','10');",
				$this->queryBuilder->toSQL()
		 );
	 }

	 public function testAndWhereOneInModePrepared() {
		 $this->queryBuilder
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->select('t2', 'colonne1 as col1', 'colonne2 as col2')
				->from('table1 t1')
				->innerJoin('table2 t2', 't1.champ1 = t2.colonne2')
				->andWhere('t2.colonne1 = :col2', '400')
				->in('t2.colonne2', [1, 10], ['val1', 'val2']);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2, t2.colonne1 as col1, t2.colonne2 as col2 FROM table1 t1 INNER JOIN table2 t2 ON t1.champ1 = t2.colonne2 WHERE t2.colonne1 = :col2 AND t2.colonne2 IN (:val1,:val2);",
				$this->queryBuilder->toSQL()
		 );
	 }


	 public function testAndWhereMultiplesInModePrepared() {
		 $this->queryBuilder
				->select('t1', 'champ1 as c1', 'champ2 as c2')
				->select('t2', 'colonne1 as col1', 'colonne2 as col2')
				->from('table1 t1')
				->innerJoin('table2 t2', 't1.champ1 = t2.colonne2')
				->andWhere('t2.colonne1 = :col2', '400')
				->in('t1.colonne1', [1, 10], ['val1', 'val2'])
				->in('t2.colonne2', [2, 20], ['val3', 'val4']);

		 $this->assertEquals(
				"SELECT t1.champ1 as c1, t1.champ2 as c2, t2.colonne1 as col1, t2.colonne2 as col2 FROM table1 t1 INNER JOIN table2 t2 ON t1.champ1 = t2.colonne2 WHERE t2.colonne1 = :col2 AND t1.colonne1 IN (:val1,:val2) AND t2.colonne2 IN (:val3,:val4);",
				$this->queryBuilder->toSQL()
		 );
	 }

//    public function testSetDefaultParams() {}
//
//    public function testFormatParams() {}


}