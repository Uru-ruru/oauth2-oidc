<?php

use Uru\BitrixMigrations\BaseMigrations\BitrixMigration;
use Uru\BitrixMigrations\Constructors\Constructor;
use Uru\BitrixMigrations\Constructors\HighloadBlock;
use Uru\BitrixMigrations\Constructors\UserField;
use Uru\BitrixMigrations\Helpers;

class AddUserToken20220605113102690552 extends BitrixMigration
{
    /**
     * Run the migration.
     *
     * @throws Exception
     */
    public function up(): void
    {
        $hlBlockId = (new HighloadBlock())
            ->constructDefault('Oauth2Tokens', 'oauth_tokens')
            ->setLang('en', 'Oauth2 Tokens')
            ->add()
        ;

        if ($hlBlockId) {
            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_CLIENT')
                ->setUserTypeHL('oauth_clients', 'UF_NAME')
                ->setLangDefault('en', 'Client')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_IDENTIFIER')
                ->setUserType('string')
                ->setIsSearchable(true)
                ->setLangDefault('en', 'Identifier')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_SCOPES')
                ->setUserType('string')
                ->setMultiple(true)
                ->setLangDefault('en', 'Scopes')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_EXPIRY_DATETIME')
                ->setUserType('datetime')
                ->setLangDefault('en', 'Expiry DateTime')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_USER')
                ->setUserType('users')
                ->setLangDefault('en', 'User Identifier')
                ->add()
            ;
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     *
     * @throws \Uru\BitrixMigrations\Exceptions\MigrationException
     */
    public function down(): void
    {
        $hlData = Helpers::getHlId('oauth_tokens');

        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_CLIENT'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_IDENTIFIER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_SCOPES'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_EXPIRY_DATETIME'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_USER'));

        HighloadBlock::delete('oauth_tokens');
    }
}
