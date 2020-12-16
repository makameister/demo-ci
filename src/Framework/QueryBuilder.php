<?php

namespace Framework;

/**
 * Class QueryBuilder.
 *
 * CLasse représentant une requête SQL sous forme d'objet
 *
 * @package App\Query
 * @Author : Guillaume Franke
 * @Version 1.0 - prochaine version inclura les IN, NOT IN, HAVING, LIMIT, OFFSET
 */
class QueryBuilder
{

    public const EXECUTE = 'EXECUTE';

    public const EXECUTE_PREPARED = 'PREPARED';

    private $executionMode;

    private const SELECT = "SELECT";

    private const CALL = "CALL";

    private const INSERT_INTO = "INSERT INTO";

    private const UPDATE = "UPDATE";

    private const DELETE = "DELETE";

    private $statement;

    private $fields = [];

    private $table;

    private $where = [];

    private $joins = [];

    private $params = [];

    private $orderBy;

    private $groupBy;

    private $prefix;

    private $binds;

    private $in = [];

    private $deleteTable;

    /**
     * QueryBuilder constructor.
     */
    public function __construct()
    {
        $this->executionMode = self::EXECUTE_PREPARED;
    }

    /**
     * Précise que la requête sera executée directement
     *
     * Les paramètres seront placés directement dans la requête
     *
     * @return QueryBuilder
     */
    public function executeMode(): self
    {
        $this->executionMode = self::EXECUTE;

        return $this;
    }

    /**
     * Précise que la requête sera préparée
     *
     * Il faut récuprer les paraèmtres bindés via la méthode getBindParams() et les donner à la Query
     *
     * @return QueryBuilder
     */
    public function preparedMode(): self
    {
        $this->executionMode = self::EXECUTE_PREPARED;

        return $this;
    }

    /**
     * Initialise un SElECT
     *
     * @param string|null $tableAlias
     * @param string ...$fields
     * @return QueryBuilder
     */
    public function select(?string $tableAlias = null, ?string ...$fields): self
    {
        if (!$this->statement) {
            $this->statement = self::SELECT;
        }

        $fieldWithAliases = [];
        foreach ($fields as $item) {
            $fieldWithAliases[] = is_null($tableAlias) ? $item : "$tableAlias.$item";
        }

        if (empty($fields)) {
            $this->fields = ["*"];
        } else {
            $this->fields = array_merge($this->fields, $fieldWithAliases);
        }

        return $this;
    }

    /**
     * Permet de selectionner des champs avec un "as"
     *
     * @param string|null $tableAlias
     * @param string|null $prefix
     * @param string ...$fields
     * @return $this
     * @example : $this->selectAs('pdi', 'pdi_', 'id_ville', 'id_pays');
     *      => "SELECT pdi.pdi_id_ville as id_ville, pdi.pdi_id_pays ..."
     */
    public function selectAs(?string $tableAlias = null, ?string $prefix = null, string ...$fields): self
    {
        $this->select($tableAlias, implode(', ', array_map(function ($field) use ($prefix) {
            return "$prefix$field as $field";
        }, $fields)));

        return $this;
    }

    /**
     * Spécifie la table pour les SELECT et DELETE
     *
     * @param string $table : nom de la table et éventuellement l'alias
     * @param string|null $alias : l'alias si non passé avec la tale
     * @return QueryBuilder
     */
    public function from(string $table, ?string $alias = null): self
    {
        $this->table = is_null($alias) ? $table : "$table $alias";

        return $this;
    }

    /**
     * Initialise un INSERT
     *
     * @param string $table : table sur laquelle porte l'insert
     * @return QueryBuilder
     */
    public function insertInto(string $table): self
    {
        $this->statement = self::INSERT_INTO;
        $this->table = $table;

        return $this;
    }

    /**
     * Initialise un UPDATE
     *
     * @param string $table : table sur laquelle porte l'update
     * @return QueryBuilder
     */
    public function update(string $table): self
    {
        $this->statement = self::UPDATE;
        $this->table = $table;

        return $this;
    }

    /**
     * Initialise un DELETE
     *
     * @param string[] $table
     * @return QueryBuilder
     */
    public function delete(string ...$table): self
    {
        $this->statement = self::DELETE;
        $this->deleteTable = $table;

        return $this;
    }

    public function call(string $table): self
    {
        $this->statement = self::CALL;
        $this->table = $table;

        return $this;
    }

    /**
     * Ajoute des restrictions
     *
     * @param string $condition : la condtion de la restriction
     * @param $value : la valeur de la condition
     * @return QueryBuilder
     * @example :
     *      - EXECUTE : $queryBuilder->andWhere('colonne', 'valeur');
     *      - PREPARED : $queryBuilder->andWhere('colonne = :bind', 'valeur');
     *          Dans ce cas la valeur sera retournée avec les paramètres bindés
     *          $queryBuilder->getBindedParams(); => [bind => valeur, ...]
     * !!! Penser à ajouter les alias !!!
     *      $queryBuilder->andWhere('a.colonne = :bind', 'valeur');
     */
    public function andWhere(string $condition, $value = null): self
    {
        $this->where[] = $condition;

        if ($value === null) {
            return $this;
        }

        if ($this->executionMode === self::EXECUTE_PREPARED) {
            $bind = mb_substr($condition, mb_strpos($condition, ':') + 1, mb_strlen($condition));
            $this->binds[$bind] = $value;
        } else {
            $this->binds[$condition] = $value;
        }

        return $this;
    }

    /**
     * Ajoute une jointure interne
     *
     * @param string $table : table de jointure avec éventuellement un alias
     * @param string $condition : les colonnes sur laquelle porte la jointure
     * @return QueryBuilder
     * @example : $queryBuilder->innerJoin('table t2', 't2.colonne = t1.colonne);
     * !!! Penser à ajouter les alias !!!
     */
    public function innerJoin(string $table, string $condition): self
    {
        $this->addJoin('INNER JOIN', $table, $condition);

        return $this;
    }

    /**
     * Ajoute une jointure externe gauche
     *
     * @param string $table : table de jointure avec éventuellement un alias
     * @param string $condition : les colonnes sur lesquelles porte la jointure
     * @return QueryBuilder
     * @example : $queryBuilder->leftJoin('table t2', 't2.colonne = t1.colonne);
     * !!! Penser à ajouter les alias !!!
     */
    public function leftJoin(string $table, string $condition): self
    {
        $this->addJoin('LEFT JOIN', $table, $condition);

        return $this;
    }

    /**
     * Ajoute une instruction IN
     *
     * @param string $condition : nom de la colonne (inclus alias table) sur laquelle s'applique le IN
     * @param array $values : liste des valeurs dans le IN
     * @param array $bindNames : si requête préparée, liste des noms de paramètres bind à intégrer dans le IN. Si ce tableau est donné
     *                                     vide alors que le mode est EXECUTE_PREPARED alors le IN généré sera celui du mode EXECUTE
     * @return QueryBuilder
     * @example :
     *      - EXECUTE : $queryBuilder->AndIn('colonne', ['valeur1', 'valeur2',...], ['nomParam1', 'nomParam2',...]);
     *                        Dans le IN ce sont les valeurs qui seront utilisées
     *      - PREPARED : AndIn('colonne', ['valeur1', 'valeur2',...], [':nomParam1', ':nomParam2',...]);
     *                        Dans le IN ce sont les noms de paramètres qui seront utilisés
     *        Dans ce cas la valeur sera retournée avec les paramètres bindés $queryBuilder->getBindedParams(); => [bind => valeur, ...]
     */
    public function in(string $condition, array $values, array $bindNames = []): self
    {
        if ($this->executionMode === self::EXECUTE_PREPARED and !empty($bindNames)) {
            $in_bindNames = ':' . implode(',:', $bindNames);
            $this->in[] = $condition . ' IN (' . $in_bindNames . ')';
            $cpt = 0;
            foreach ($values as $val2Bind) {
                $this->binds[$bindNames[$cpt]] = $val2Bind;
                $cpt++;
            }
        } else {
            $in_values = implode("','", $values);
            $this->in[] = $condition . " IN ('" . $in_values . "')";
        }

        return $this;
    }

    /**
     * Spécifie l'ordre de retour des données
     *
     * @param string $order : l'ordre souhaité [ASC ou DESC]
     * @param string ...$fields : la liste des colonnes
     * @example :
     *      $queryBuilder->orderBy('ASC', 'champ1', champ2');
     *      $queryBuilder->orderBy('DESC', 'champ4', champ5');
     * @return QueryBuilder
     */
    public function orderBy(string $order, string ...$fields): self
    {
        $orders = implode(', ', $fields) . " $order";
        $this->orderBy .= empty($this->orderBy) ? $orders : ", $orders";

        return $this;
    }

    /**
     * Regroupe le résultat par colonne
     *
     * @param string ...$fiedls : liste des champs
     * @return QueryBuilder
     */
    public function groupBy(string ...$fiedls): self
    {
        $this->groupBy = implode(', ', $fiedls);

        return $this;
    }

    /**
     * Affecte les données qui seront utilisées pour les INSERT et UPDATE
     *
     * @param array $params : tableau associatif des données (ex: formulaire html)
     * @return QueryBuilder
     * @example :
     *      $queryBuilder->insert('table');
     *      $queryBuilder->setParams($form);
     * !!!
     *  Les clés du tableau doivent correspondre aux colonnes du SGBD
     *  il est possible d'ajouter un préfixe pour faire correspondre
     * !!!
     */
    public function setParams(array $params): self
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Néttoie les paramètres
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->statement = null;
        $this->fields = [];
        $this->table = null;
        $this->where = [];
        $this->joins = [];
        $this->params = [];
        $this->orderBy = null;
        $this->groupBy = null;
        $this->prefix = null;
        $this->binds = null;

        return $this;
    }

    /**
     * Permet de remplacer une valeur vide par une autre si la clé du tableau contient la condition
     *
     * @param $conditions : le ou les conditions, une sous-chaine ou la chaine complète
     * @param $replace : valeur de remplacement
     *
     * @return $this
     * @example : $form = ["id_personne" => 147, "id_ville" => "", "annee" => ""];
     *      $queryBuilder->setParams($form);
     *
     * $queryBuilder->setDefaultParamsValue("id_", 0);
     *      ==> ["id_personne" => 147, "id_ville" => 0, "annee" => ""]
     *
     * $queryBuilder->setDefaultParamsValue(["id_", "annee"], 0);
     *      ==> ["id_personne" => 147, "id_ville" => 0, "annee" => 0]
     *
     * $queryBuilder->setDefaultParamsValue(["id_ville", "annee_universitaire"], 0);
     *      ==> ["id_personne" => 147, "id_ville" => 0, "annee" => ""]
     *
     */
    public function setDefaultParamsValue($conditions, $replace): self
    {
        if (!is_array($conditions)) {
            $conditions = [$conditions];
        }

        foreach ($this->params as $key => $value) {
            foreach ($conditions as $condition) {
                if (strpos($key, $condition) !== false && $value === "") {
                    $this->params[$key] = $replace;
                }
            }
        }

        return $this;
    }


    /**
     * Permet de formater les paramètres via une fonction
     *
     * @param string|callable $function : la function à appliquer
     * @param string ...$keys : la liste des clé du tableau de paramètres
     *
     * @return $this
     * @example :
     *  Fonction simple (sous forme de string) :
     *      $queryBuilder->formatParams('ucfirst', 'nom_personne');
     *  Fonction définie par un développeur (sous former de callable) :
     *      $queryBuilder->formatParams([StringHelper::class, 'ucwordsWithAccentedLetters'], 'nom_personne');
     *
     * !!! Callable => [NomDeLaClasse:class, 'nomDeLaMéthode'] !!!
     */
    public function formatParams($function, string ...$keys): self
    {
        foreach ($keys as $key) {
            if (is_callable($function)) {
                $this->params[$key] = call_user_func($function, $this->params[$key]);
            }
            $this->params[$key] = $function($this->params[$key]);
        }

        return $this;
    }

    /**
     * Ajoute le préfixe aux clés du tableau de paramètres
     *
     * @param string $prefix : le préfix des colonnes en SGBD
     * @return QueryBuilder
     * @example :
     *  table_prefix : {prefix_colonne1, prefix_colonne2, ...}
     *  $form : [clé1 => valeur1, clé2 => valeur2, ...]
     *  $queryBuilder->setPrefix('prefix_');
     *  $queryBuilder->getBindedParams() : [prefix_clé1 => valeur1, ...]
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Retourne la requête SQL
     *
     * @return String
     */
    public function toSQL(): string
    {
        $sql = $this->statement;

        if ($this->statement === self::CALL) {
            $sql .= " {$this->table} {$this->buildCall()}";
            return $sql;
        }

        if ($this->statement === self::SELECT) {
            $sql .= " " . implode(', ', $this->fields) . " FROM $this->table";
        } elseif ($this->statement === self::DELETE) {
            if ($this->deleteTable !== []) {
                $sql .= " " . implode(', ', $this->deleteTable) . " FROM $this->table";
            } else {
                $sql .= " FROM $this->table";
            }
        } else {
            $sql .= " $this->table ";
        }

        if ($this->statement !== self::INSERT_INTO && !empty($this->joins)) {
            if ($this->statement === self::DELETE || $this->statement === self::SELECT) {
                $sql .= " " . implode(' ', $this->joins);
            } else {
                $sql .= implode(' ', $this->joins);
            }
        }

        if ($this->statement === self::INSERT_INTO) {
            $sql .= $this->buildInsert();
        } elseif ($this->statement === self::UPDATE) {
            $sql .= empty($this->joins) ? "SET " : " SET ";
            $sql .= $this->buildUpdate();
        }

        if ($this->statement !== self::INSERT_INTO && (!empty($this->where) || !empty($this->in))) {
            $sql .= " WHERE ";
            if (!empty($this->where)) {
                if ($this->executionMode === self::EXECUTE) {
                    $sql .= implode(' AND ', array_map(function ($key, $value) {
                        return is_string($value) ? "$key = '$value'" : "$key = $value";
                    }, array_keys($this->binds), array_values($this->binds)));
                } else {
                    $sql .= implode(" AND ", $this->where);
                }
            }
            if (!empty($this->in)) {
                $operateur = (!empty($this->where)) ? ' AND ' : '';
                $sql .= $operateur . implode(' AND ', $this->in);
                unset($operateur);
            }
        }

        if ($this->statement === self::SELECT) {
            if ($this->groupBy) {
                $sql .= " GROUP BY $this->groupBy";
            }
            if ($this->orderBy) {
                $sql .= " ORDER BY $this->orderBy";
            }
        }

        return $sql . ";";
    }

    /**
     * Retourne les paramètres pour une requête préparée
     *
     * @return array : Paramètre binded de la requête et des restrictions
     *      [clé1 => valeur1, clé => valeur2, bind => 10]
     * @example :
     *      $queryBuilder->insert('table')
     *      $queryBuilder->setParams([clé1 => valeur1, clé2 => valeur2]);
     *      $queryBuilder->andWhere('colonne1 = :bind', 10);
     *
     */
    public function getBindParams(): array
    {
        return is_array($this->binds) ? array_merge($this->params, $this->binds) : $this->params;
    }

    /**
     * Permet d'exécuter la requête via le parent
     *
     * @return $this
     */
    public function exec(): self
    {
        if ($this->executionMode === self::EXECUTE) {
            $this->sql = $this->toSQL();
            $this->execute();
        } else {
            $this->sql = $this->toSQL();
            $this->executePrepared($this->getBindParams());
        }

        return $this;
    }

    /**
     * Crée le corps de la requête INSERT via les paramètres passés
     *
     * @return string
     */
    private function buildInsert(): string
    {
        $sql = "";

        $prefix = $this->prefix;

        if ($this->executionMode === self::EXECUTE) {
            $sql .= $this->betweenParenthesis(implode(', ', array_map(function ($column) use ($prefix) {
                return is_null($prefix) ? $column : $prefix . $column;
            }, array_keys($this->params))));

            $sql .= " VALUES ";

            $sql .= $this->betweenParenthesis(implode(', ', array_map(function ($value) {
                return is_string($value) ? "'$value'" : $value;
            }, array_values($this->params))));
        } else {
            $sql .= $this->betweenParenthesis(implode(', ', array_map(function ($column) use ($prefix) {
                return is_null($prefix) ? $column : $prefix . $column;
            }, array_keys($this->params))));

            $sql .= " VALUES ";

            $sql .= $this->betweenParenthesis(implode(', ', array_map(function ($bind) {
                return ":$bind";
            }, array_keys($this->params))));
        }

        return $sql;
    }

    /**
     * Crée le corps de la requête UPDATE via les paramètres passés
     *
     * @return string
     */
    private function buildUpdate(): string
    {
        $prefix = $this->prefix;

        if ($this->executionMode === self::EXECUTE) {
            return implode(', ', array_map(function ($column, $value) use ($prefix) {
                $column = is_null($prefix) ? "$column = " : "$prefix$column = ";
                $value = is_string($value) ? "'$value'" : $value;
                return $column . $value;
            }, array_keys($this->params), $this->params));
        } else {
            return implode(', ', array_map(function ($column) use ($prefix) {
                return is_null($prefix) ? "$column = :$column" : "$prefix$column = :$column";
            }, array_keys($this->params)));
        }
    }

    private function buildCall(): string
    {
        $prefix = $this->prefix;

        return $this->betweenParenthesis(implode(', ', array_map(function ($column) use ($prefix) {
            return is_null($prefix) ? ":$column" : $prefix . ":$column";
        }, array_keys($this->params))));
    }

    /**
     * Place une chaine de caractères entre des parenthèses
     *
     * @param string $string
     * @return string
     */
    private function betweenParenthesis(string $string): string
    {
        return "(" . $string . ")";
    }

    /**
     * Ajoute les jointures en fonction de leur type
     *
     * @param string $joinType
     * @param string $table
     * @param string $condition
     */
    private function addJoin(string $joinType, string $table, string $condition): void
    {
        $this->joins[] = "$joinType $table ON $condition";
    }

    /**
     * Débugger
     *
     * @param \Exception|null $e
     */
    public function debug(?\Exception $e = null)
    {
        var_dump($this->toSQL());
        var_dump($this->getBindParams());
        if (!is_null($e)) {
            var_dump($e->getMessage());
        }
    }
}
