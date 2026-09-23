<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\DQL\Sqlite;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class SqliteFromUnixtime extends FunctionNode
{
    private Node $timestamp;

    public function parse(Parser $parser): void
    {
        $parser->match(\Doctrine\ORM\Query\TokenType::T_IDENTIFIER);
        $parser->match(\Doctrine\ORM\Query\TokenType::T_OPEN_PARENTHESIS);

        $this->timestamp = $parser->ArithmeticPrimary();
        $parser->match(\Doctrine\ORM\Query\TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $this->timestamp->dispatch($sqlWalker);
    }
}
