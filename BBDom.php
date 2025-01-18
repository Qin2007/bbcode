<?php
//require_once 'BBcode.php';
//require_once 'BBElement.php';
//class BBDom
//{
//    private $root;
//
//    /**
//     * @throws ErrorException
//     */
//    private function __construct(BBCode $BBCode)
//    {
//        $ast = $BBCode->getAbstractSyntaxTree();
//        if (is_null($ast)) throw new ErrorException('parse the document first');
//        $parser = array();
//        foreach ($ast->getChildren() as $child) {
//
//        }
//    }
//
//    public static function createFrom(BBCode|string $code): self
//    {
//        if (is_string($code)) {
//            $code = (new BBCode($code));
//        }
//        return new self($code->parse());
//    }
//}