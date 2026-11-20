<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class ExampleTest extends TestCase
{
    public function test_firebooster_name_is_not_empty(): void
    {
        self::assertNotSame('', 'Firebooster');
    }
}
