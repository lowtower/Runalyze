<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;

class Sha256SaltedEncoderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sha256SaltedEncoder
     */
    protected $Encoder;

    protected function setUp(): void
    {
        $this->Encoder = new Sha256SaltedEncoder();
    }

    public function testValidation(): void
    {
        $this->assertTrue(
            $this->Encoder->verify(
                $this->Encoder->hash('Th1$PassSwordIsSoDamnGreat!', 'Salt and pepper ...'),
                'Th1$PassSwordIsSoDamnGreat!',
                'Salt and pepper ...'
            )
        );
    }

    public function testValidationOfWrongPassword(): void
    {
        $this->assertFalse(
            $this->Encoder->verify(
                $this->Encoder->hash('Th1$PassSwordIsSoDamnGreat!', 'A password is no sword.'),
                'Th1$PasSwordIsSoDamnGreat!',
                'A password is no sword.'
            )
        );
    }
}
