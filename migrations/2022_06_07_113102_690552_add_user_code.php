<?php

use Uru\BitrixMigrations\BaseMigrations\BitrixMigration;
use Uru\BitrixMigrations\Constructors\Constructor;
use Uru\BitrixMigrations\Constructors\HighloadBlock;
use Uru\BitrixMigrations\Constructors\UserField;
use Uru\BitrixMigrations\Helpers;

class AddUserCode20220607113102690552 extends BitrixMigration
{
    /**
     * Run the migration.
     *
     * @throws Exception
     */
    public function up(): void
    {
        $hlBlockId = (new HighloadBlock())
            ->constructDefault('Oauth2Codes', 'oauth_codes')
            ->setLang('en', 'Oauth2 Codes')
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

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_REDIRECT_URI')
                ->setUserType('string')
                ->setIsSearchable(true)
                ->setLangDefault('en', 'Redirect URI')
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
        $hlData = Helpers::getHlId('oauth_codes');

        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_CLIENT'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_IDENTIFIER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_SCOPES'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_EXPIRY_DATETIME'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_USER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_REDIRECT_URI'));

        HighloadBlock::delete('oauth_codes');
    }
}
