<?php

namespace EMS\CommonBundle\Tests\Unit\Helper\Text;

use EMS\CommonBundle\Json\JsonMenu;
use PHPUnit\Framework\TestCase;

class JsonMenuTest extends TestCase
{
    /** @var JsonMenu */
    private $jsonMenu;

    protected function setUp(): void
    {
        $source = '{"a":1,"b":2,"c":3,"d":4,"e":5}'; //'{"test":test}'; //should be a json  '{"a":1,"b":2,"c":3,"d":4,"e":5}'
        $glue = "testGlue"; // \something
        $this->jsonMenu = new JsonMenu($source,$glue);
        parent::setUp();
    }

    public function testConstructorMostlyGetter()
    {
        self::assertSame('{"a":1,"b":2,"c":3,"d":4,"e":5}', $this->jsonMenu->getJson());
        //self::assertSame("testGlue", $this->jsonMenu->getGlue());
        //self::assertSame([], $this->jsonMenu->getSlugs());
    }

    public function cloneProvider()
    {
        $source = '{"a":1,"b":2,"c":3,"d":4,"e":5}'; //'{"test":test}'; //should be a json  '{"a":1,"b":2,"c":3,"d":4,"e":5}'
        $glue = "testGlue";
        return $this->cloneJsonMenu = new JsonMenu($source,$glue);
    }

    /**
     * @dataProvider cloneProvider()
     */
    public function testObjectSame(JsonMenu $cloneJsonMenu)
    {
        self::assertEquals($this->jsonMenu, $cloneJsonMenu);
    }

    public function resultProvider()
    {
        return '"structure": "[{\"id\":\"102a603e-b2ab-499d-b1d3-687a2e4ee168\",\"type\":\"theme\",\"object\":{\"label_nl\":\"testExampleAdrien\",\"label_fr\":\"testExempleAdrien\"},\"label\":\"testAdrien\",\"children\":[{\"id\":\"7b4f228f-3d04-4eb0-a826-aeaa1e8bc8aa\",\"object\":{\"label_nl\":\"testDocNl\",\"label_fr\":\"testDocFr\"},\"type\":\"theme_document\",\"label\":\"testDoc\"}]}]","_sha1": "4433d463c793f2a0471faaa66ce074b2bf8014b3"';
    }

    /**
     * @dataProvider resultProvider()
     */
    public function testRecursiveWalk(String $result)
    {
        dump($result);
        return null;    
    }


    /*   
    private function recursiveWalk(array $menu, string $basePath = ''): void
    {
        foreach ($menu as $item) {
            $slug = $basePath.$item['label'];
            $this->items[$item['id']] = $item;
            $this->slugs[$item['id']] = $slug;
            $this->bySlugs[$slug] = $item;
            if (isset($item['children'])) {
                $this->recursiveWalk($item['children'], $slug.$this->glue);
            }
        }
    } 
    */
}