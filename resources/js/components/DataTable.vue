<script setup lang="ts" generic="T extends Record<string, unknown>">
import EmptyState from '@/components/EmptyState.vue';
import { cn } from '@/lib/utils';
import type { DataTableColumn } from '@/types';

const props = defineProps<{
    columns: DataTableColumn[];
    rows: T[];
    rowKey: keyof T & string;
    caption?: string;
    emptyTitle: string;
    emptyDescription?: string;
}>();

function alignment(column: DataTableColumn): string {
    return column.align === 'right'
        ? 'text-right'
        : column.align === 'center'
          ? 'text-center'
          : 'text-left';
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption v-if="props.caption" class="sr-only">
                    {{
                        props.caption
                    }}
                </caption>

                <thead>
                    <tr class="border-b bg-muted/40">
                        <th
                            v-for="column in props.columns"
                            :key="column.key"
                            scope="col"
                            :class="
                                cn(
                                    'px-4 py-3 font-medium',
                                    alignment(column),
                                    column.class,
                                )
                            "
                        >
                            {{ column.label }}
                            <span v-if="column.srLabel" class="sr-only">
                                {{ column.srLabel }}
                            </span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(row, index) in props.rows"
                        :key="String(row[props.rowKey])"
                        class="border-b transition-colors last:border-b-0 hover:bg-muted/40"
                    >
                        <slot name="row" :row="row" :index="index" />
                    </tr>

                    <tr v-if="!props.rows.length">
                        <td :colspan="props.columns.length" class="p-0">
                            <slot name="empty">
                                <EmptyState
                                    class="rounded-none border-0 bg-transparent"
                                    :title="props.emptyTitle"
                                    :description="props.emptyDescription"
                                />
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
