<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplies')) {
            return;
        }

        if (! Schema::hasColumn('supplies', 'lead_time_days')) {
            Schema::table('supplies', function (Blueprint $table) {
                $table->unsignedInteger('lead_time_days')->default(0)->after('supplies_alert');
            });
        }
        if (! Schema::hasColumn('supplies', 'safety_stock')) {
            Schema::table('supplies', function (Blueprint $table) {
                $table->unsignedInteger('safety_stock')->default(0)->after('lead_time_days');
            });
        }

        if (Schema::hasTable('supplies_variants')) {
            if (Schema::hasColumn('supplies_variants', 'lead_time_days')) {
                DB::statement(
                    'UPDATE `supplies` s
                     INNER JOIN (
                         SELECT `supplies_id`, MAX(COALESCE(`lead_time_days`, 0)) AS `lead_time_days`
                         FROM `supplies_variants`
                         GROUP BY `supplies_id`
                     ) sv ON sv.`supplies_id` = s.`supplies_id`
                     SET s.`lead_time_days` = GREATEST(COALESCE(s.`lead_time_days`, 0), sv.`lead_time_days`)'
                );
            }
            if (Schema::hasColumn('supplies_variants', 'safety_stock')) {
                DB::statement(
                    'UPDATE `supplies` s
                     INNER JOIN (
                         SELECT `supplies_id`, MAX(COALESCE(`safety_stock`, 0)) AS `safety_stock`
                         FROM `supplies_variants`
                         GROUP BY `supplies_id`
                     ) sv ON sv.`supplies_id` = s.`supplies_id`
                     SET s.`safety_stock` = GREATEST(COALESCE(s.`safety_stock`, 0), sv.`safety_stock`)'
                );
            }
        }

        DB::statement('UPDATE `supplies` SET `lead_time_days` = GREATEST(COALESCE(`lead_time_days`, 0), 0)');
        DB::statement('UPDATE `supplies` SET `safety_stock` = GREATEST(COALESCE(`safety_stock`, 0), 0)');
        DB::statement('ALTER TABLE `supplies` MODIFY `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `supplies` MODIFY `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0');

        if (Schema::hasTable('supplies_variants')) {
            if (Schema::hasColumn('supplies_variants', 'safety_stock')) {
                Schema::table('supplies_variants', fn (Blueprint $table) => $table->dropColumn('safety_stock'));
            }
            if (Schema::hasColumn('supplies_variants', 'lead_time_days')) {
                Schema::table('supplies_variants', fn (Blueprint $table) => $table->dropColumn('lead_time_days'));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplies_variants')) {
            if (! Schema::hasColumn('supplies_variants', 'lead_time_days')) {
                Schema::table('supplies_variants', function (Blueprint $table) {
                    $table->unsignedInteger('lead_time_days')->default(0)->after('supplies_variant_price');
                });
            }
            if (! Schema::hasColumn('supplies_variants', 'safety_stock')) {
                Schema::table('supplies_variants', function (Blueprint $table) {
                    $table->unsignedInteger('safety_stock')->default(0)->after('lead_time_days');
                });
            }

            if (Schema::hasTable('supplies') && Schema::hasColumn('supplies', 'lead_time_days')) {
                DB::statement(
                    'UPDATE `supplies_variants` sv
                     INNER JOIN `supplies` s ON s.`supplies_id` = sv.`supplies_id`
                     SET sv.`lead_time_days` = s.`lead_time_days`'
                );
            }
            if (Schema::hasTable('supplies') && Schema::hasColumn('supplies', 'safety_stock')) {
                DB::statement(
                    'UPDATE `supplies_variants` sv
                     INNER JOIN `supplies` s ON s.`supplies_id` = sv.`supplies_id`
                     SET sv.`safety_stock` = s.`safety_stock`'
                );
            }
        }

        if (Schema::hasTable('supplies')) {
            if (Schema::hasColumn('supplies', 'safety_stock')) {
                Schema::table('supplies', fn (Blueprint $table) => $table->dropColumn('safety_stock'));
            }
            if (Schema::hasColumn('supplies', 'lead_time_days')) {
                Schema::table('supplies', fn (Blueprint $table) => $table->dropColumn('lead_time_days'));
            }
        }
    }
};
