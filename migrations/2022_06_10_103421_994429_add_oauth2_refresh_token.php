<?php

use Uru\BitrixMigrations\BaseMigrations\BitrixMigration;
use Uru\BitrixMigrations\Constructors\Constructor;
use Uru\BitrixMigrations\Constructors\HighloadBlock;
use Uru\BitrixMigrations\Constructors\UserField;
use Uru\BitrixMigrations\Helpers;

class AddOauth2RefreshToken20220610103421994429 extends BitrixMigration
{
    /**
     * Run the migration.
     *
     * @throws Exception
     */
    public function up(): void
    {
        $hlBlockId = (new HighloadBlock())
            ->constructDefault('Oauth2RefreshTokens', 'oauth_refresh_tokens')
            ->setLang('en', 'Oauth2 Refresh Tokens')
            ->add()
        ;

        if ($hlBlockId) {
            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_ACCESS_TOKEN')
                ->setUserType('string')
                ->setLangDefault('en', 'Access Token')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_IDENTIFIER')
                ->setUserType('string')
                ->setIsSearchable(true)
                ->setLangDefault('en', 'Identifier')
                ->add()
            ;

            (new UserField())->constructDefault(Constructor::objHLBlock($hlBlockId), 'UF_EXPIRY_DATETIME')
                ->setUserType('datetime')
                ->setLangDefault('en', 'Expiry DateTime')
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
        $hlData = Helpers::getHlId('oauth_refresh_tokens');

        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_ACCESS_TOKEN'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_IDENTIFIER'));
        (new CUserTypeEntity())->delete((array) $this->getUFIdByCode('HLBLOCK_'.$hlData, 'UF_EXPIRY_DATETIME'));

        HighloadBlock::delete('oauth_refresh_tokens');
    }
}
