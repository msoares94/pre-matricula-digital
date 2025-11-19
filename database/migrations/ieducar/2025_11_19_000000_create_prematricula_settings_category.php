<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CRIAR CATEGORIA "Pré-Matrícula Digital"
        $categoryId = DB::selectOne("
            SELECT id FROM settings_categories
            WHERE name = 'Pré-Matrícula Digital'
        ");

        if ($categoryId) {
            $categoryId = $categoryId->id;
        } else {
            $result = DB::selectOne("
                INSERT INTO settings_categories (name, enabled, created_at, updated_at)
                VALUES ('Pré-Matrícula Digital', true, NOW(), NOW())
                RETURNING id;
            ");
            $categoryId = $result->id;
        }

        // 2. ATUALIZAR SETTINGS EXISTENTES PARA A NOVA CATEGORIA
        DB::statement("
            UPDATE settings
            SET setting_category_id = ?
            WHERE key LIKE 'prematricula.%';
        ", [$categoryId]);

        $settings = [
            // CONFIGURAÇÕES BÁSICAS
            ['prematricula.active', 'true', 'boolean', 'Ativar/Desativar o Pré-Matrícula Digital'],
            ['prematricula.ibge_codes', '', 'string', 'Códigos IBGE separados por vírgula'],

            // IDENTIDADE VISUAL
            ['prematricula.logo', '/intranet/imagens/brasao-republica.png', 'string', 'Caminho do logo do município'],
            ['prematricula.slogan', 'Prefeitura Municipal de ', 'string', 'Slogan exibido no cabeçalho'],

            // MAPA
            ['prematricula.map_latitude', '-28.7', 'string', 'Latitude do centro do mapa'],
            ['prematricula.map_longitude', '-49.3', 'string', 'Longitude do centro do mapa'],
            ['prematricula.map_zoom', '13', 'integer', 'Nível de zoom padrão do mapa'],

            // FUNCIONALIDADES
            ['prematricula.allow_optional_address', 'true', 'boolean', 'Permitir endereço opcional'],
            ['prematricula.show_how_to_do_video', 'true', 'boolean', 'Exibir vídeo tutorial'],
            ['prematricula.legacy', 'true', 'boolean', 'Usar modo legado (integrado ao i-Educar)'],
            ['prematricula.link_to_restrict_area', null, 'string', 'Link para área restrita'],

            // FEATURES
            ['prematricula.allow_preregistration_data_update', 'true', 'boolean', 'Permitir atualização de dados da pré-matrícula'],
            ['prematricula.allow_external_system_data_update', 'true', 'boolean', 'Permitir atualização de dados do sistema externo'],
            ['prematricula.allow_transfer_registration', 'false', 'boolean', 'Permitir transferência de matrícula'],
            ['prematricula.transfer_description', 'Transferência Pré-matrícula Digital', 'string', 'Descrição da transferência'],
            ['prematricula.allow_vacancy_certificate', 'false', 'boolean', 'Permitir certificado de vaga'],

            // INTEGRAÇÃO MINHA VAGA NA CRECHE
            ['prematricula.minha_vaga_na_creche_url', null, 'string', 'URL do sistema Minha Vaga na Creche'],
            ['prematricula.minha_vaga_na_creche_token', null, 'string', 'Token do sistema Minha Vaga na Creche'],

            // OUTROS
            ['prematricula.user', '1', 'integer', 'ID do usuário padrão para operações'],
        ];

        foreach ($settings as $setting) {
            [$key, $value, $type, $description] = $setting;

            DB::statement("
                INSERT INTO settings (key, value, type, description, setting_category_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON CONFLICT (key) DO UPDATE SET
                    type = EXCLUDED.type,
                    description = EXCLUDED.description,
                    setting_category_id = EXCLUDED.setting_category_id,
                    updated_at = NOW();
            ", [$key, $value, $type, $description, $categoryId]);
        }
    }

    public function down(): void
    {
        $categoryId = DB::selectOne("
            SELECT id FROM settings_categories
            WHERE name = 'Pré-Matrícula Digital'
        ");

        if ($categoryId) {
            DB::statement("
                UPDATE settings
                SET setting_category_id = 1
                WHERE setting_category_id = ?;
            ", [$categoryId->id]);

            DB::statement("
                DELETE FROM settings_categories
                WHERE id = ?;
            ", [$categoryId->id]);
        }
    }
};
