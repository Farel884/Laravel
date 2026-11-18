<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            foreach (Schema::getForeignKeys('categories') as $foreignKey) {
                if (in_array('category_id', $foreignKey['columns'], true)) {
                    Schema::table('categories', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }
        }

        foreach (Schema::getIndexes('categories') as $index) {
            if ($index['unique'] && $index['columns'] === ['product-code']) {
                Schema::table('categories', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            } elseif ($index['columns'] === ['category_id']) {
                Schema::table('categories', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index['name']);
                });
            }
        }

        if (Schema::hasColumn('categories', 'brand')) {
            if (Schema::hasColumn('categories', 'description')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('brand');
                });
            } else {
                Schema::table('categories', function (Blueprint $table) {
                    $table->renameColumn('brand', 'description');
                });
            }
        }

        $categoryColumnsToDrop = array_values(array_filter(
            ['category_id', 'product-code', 'price', 'stock'],
            fn (string $column): bool => Schema::hasColumn('categories', $column)
        ));

        if ($categoryColumnsToDrop !== []) {
            Schema::table('categories', function (Blueprint $table) use ($categoryColumnsToDrop) {
                $table->dropColumn($categoryColumnsToDrop);
            });
        }

        if (! Schema::hasColumn('categories', 'description')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name', 100)->change();
        });

        $categoryNameIndexExists = collect(Schema::getIndexes('categories'))
            ->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['name']);

        if (! $categoryNameIndexExists) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique('name');
            });
        }

        if (Schema::hasColumn('products', 'product-code') && ! Schema::hasColumn('products', 'product_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('product-code', 'product_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_code', 'product-code');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
            $table->renameColumn('description', 'brand');
            $table->string('name', 150)->change();
            $table->unsignedBigInteger('category_id')->nullable()->after('id');
            $table->string('product-code', 50)->nullable()->unique();
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
        });

        DB::table('categories')->whereNull('category_id')->update([
            'category_id' => DB::raw('id'),
        ]);
        DB::table('categories')->whereNull('product-code')->update([
            'product-code' => DB::raw("CONCAT('CAT-', id)"),
        ]);

        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->string('product-code', 50)->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }
};
