<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\DQL\Sqlite;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

final class SqliteMonth extends FunctionNode
{
    private Node $expression;

    public function parse(Parser $parser): void
    {
        $parser->match(\Doctrine\ORM\Query\TokenType::T_IDENTIFIER);
        $parser->match(\Doctrine\ORM\Query\TokenType::T_OPEN_PARENTHESIS);

        $this->expression = $parser->ArithmeticPrimary();
        $parser->match(\Doctrine\ORM\Query\TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return "CAST(strftime('%m', datetime(" . $this->expression->dispatch($sqlWalker) . ", 'unixepoch')) AS INTEGER)";
    }
}
