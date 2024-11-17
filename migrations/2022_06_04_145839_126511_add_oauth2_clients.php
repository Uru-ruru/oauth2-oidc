<?php

use Uru\BitrixMigrations\BaseMigrations\BitrixMigration;
use Uru\BitrixMigrations\Constructors\Constructor;
use Uru\BitrixMigrations\Constructors\HighloadBlock;
use Uru\BitrixMigrations\Constructors\UserField;
use Uru\BitrixMigrations\Exceptions\MigrationException;
use Uru\BitrixMigrations\Helpers;

class AddOauth2Clients20220604145839126511 extends BitrixMigration
{
    /**
     * Run the migration.
     *
     * @throws Exception
     */
    public function up(): void
    {
        $hlBlockId = (new HighloadBlock())
            ->constructDefault('Oauth2Clients', 'oauth_clients')
            ->setLang('en', 'Oauth2 Clients')
            ->add()
        ;

        if ($hlBlockId) {
            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_NAME')
                ->setUserType('string')
                ->setLangDefault('en', 'Client name')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_SECRET')
                ->setUserType('string')
                ->setLangDefault('en', 'Client secret')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_REDIRECT_URI')
                ->setUserType('string')
                ->setLangDefault('en', 'Redirect URI')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_IS_CONFIDENTIAL')
                ->setUserType('boolean')
                ->setLangDefault('en', 'Confidential')
                ->setSettings([
                    'DEFAULT_VALUE' => true,
                    'LABEL_CHECKBOX' => '-',
                ])
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_CUSTOM_VARIABLES')
                ->setUserType('string')
                ->setMultiple(true)
                ->setLangDefault('en', 'Custom Vars')
                ->add()
            ;
        }
    }

    /**
     * Reverse the migration.
     *
     * @throws MigrationException
     */
    public function down(): void
    {
        $hlData = Helpers::getHlId('oauth_clients');

        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_CLIENT'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_IDENTIFIER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_SCOPES'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_EXPIRY_DATETIME'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_USER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_REDIRECT_URI'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_CUSTOM_VARIABLES'));

        HighloadBlock::delete('oauth_clients');
    }
}
