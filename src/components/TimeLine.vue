<template>
    <section class="timeline">
    <h2 class="timeline__headline">Was bisher geschah ...</h2>
    <p>Die Zusammenfassungen sind machinell erstellt, bitte ggf. die Quelle beachten. 
        Wir übernehmen keine Gewähr für die Richtigkeit der Inhalte.</p>
        <!-- Sorting control (optional UI) -->
        <div class="timeline__controls" v-if="showSortControls">
            <label>
                {{ $t("sort direction") }}:
                <select v-model="sortDirection">
                    <option value="asc">{{$t("oldest")}} → {{$t("newest")}}</option>
                    <option value="desc">{{$t("newest")}} → {{$t("oldest")}}</option>
                </select>
            </label>
        </div>

        <ul class="timeline__list">
            <li v-for="(item, idx) in sortedItems" :key="idx" class="timeline__item">
                <!-- Timeline marker -->
                <div class="timeline__marker">
                    <i :class="item.icon" class="marker__icon"></i>
                </div>

                <!-- Content block -->
                <div class="timeline__content">
                    <header class="content__header">
                        <time class="header__date">{{ formatDate(item.file_date) }}</time>
                        <span class="header__origin">Beteiligt: {{ item.origin }}</span>
                        <span class="header__url">Quelle: <a :href="item.url" target="_blank">{{ item.url }}</a></span>
                    </header>

                    <p class="content__summary" :class="{ truncated: !expanded[idx] }" ref="summaryRefs" @click.stop>
                        {{ item.summary }}
                    </p>

                    <!-- “More / Less” button – shown only when truncation is needed -->
                    <button v-if="needsToggle[idx]" class="summary__toggle" @click="toggle(idx)">
                        {{ expanded[idx] ? 'Less' : 'More' }}
                    </button>
                </div>
            </li>
        </ul>
    </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { format } from 'date-fns'; // optional, install with `npm i date-fns`

// ------------------- Types -------------------
interface TimelineItem {
    icon?: string | null; // e.g. "🌳" or null
    file_date: string;
    origin: string;
    summary: string;
    url?: string;
}

// ------------------- Props -------------------
const props = defineProps<{
    items: TimelineItem[];
    initialSort?: 'asc' | 'desc';
    showSortControls?: boolean;
}>();

// ------------------- Reactive state -------------------
const sortDirection = ref<'asc' | 'desc'>(props.initialSort ?? 'asc');
const expanded = ref<boolean[]>([]);          // tracks which items are expanded
const needsToggle = ref<boolean[]>([]);       // whether a given item exceeds the line limit
const summaryRefs = ref<(HTMLElement | null)[]>([]);       // refs to the <p> elements for height measurement

// ------------------- Helpers -------------------
/**
 * Returns a new array sorted according to `sortDirection`.
 * Assumes ISO‑8601 dates; falls back to lexical compare otherwise.
 */
const sortedItems = computed<TimelineItem[]>(() => {
    const copy = [...props.items];
    copy.sort((a: TimelineItem, b: TimelineItem) => {
        const aVal = new Date(a.file_date).getTime();
        const bVal = new Date(b.file_date).getTime();

        // If both parse as valid dates, compare numerically; else fallback to string compare
        const cmp = Number.isNaN(aVal) || Number.isNaN(bVal)
            ? a.file_date.localeCompare(b.file_date)
            : aVal - bVal;

        return sortDirection.value === 'asc' ? cmp : -cmp;
    });
    return copy;
});

/**
 * Simple date formatter – replace with your own locale logic if desired.
 */
function formatDate(dateStr: string): string {
    try {
        return format(new Date(dateStr), 'dd.MM.yyyy'); // e.g. "05.01.2024"
    } catch {
        return dateStr;
    }
}

/**
 * Toggle the “expanded” flag for a given index.
 */
function toggle(idx: number): void {
    expanded.value[idx] = !expanded.value[idx];
}

/**
 * After the DOM updates, determine which summaries overflow the
 * line‑clamp (≈4 lines). We store the result in `needsToggle`.
 */
async function evaluateOverflow() {
    await nextTick(); // ensure refs are populated
    needsToggle.value = summaryRefs.value.map((el) => {
        if (!el) return false;
        // Compute the height of 4 lines (line‑height × 4)
        const lineHeight = parseFloat(getComputedStyle(el).lineHeight);
        const maxHeight = lineHeight * 4;
        return el.scrollHeight > maxHeight + 1; // +1px tolerance
    });

    // Initialize the `expanded` array (all collapsed initially)
    expanded.value = needsToggle.value.map(() => false);
}

// Run once on mount and whenever the item list changes
onMounted(evaluateOverflow);
watch(
    () => props.items,
    () => {
        evaluateOverflow();
    },
    { deep: true }
);
</script>

<style scoped>
.timeline {
    width: 100%;
    max-width: 800px;
    padding: 1rem;
    font-family: system-ui, sans-serif;
    margin: 0 auto;
}

/* Optional sort selector */
.timeline__controls {
    text-align: right;
    margin-bottom: 1rem;
}

.timeline__controls select {
    padding: 0.25rem 0.5rem;
}

/* -------------------------------------------------
   Timeline layout
---------------------------------------------------*/
.timeline__list {
    position: relative;
    padding-left: 2.5rem;
    /* space for the vertical line */
    list-style: none;
    margin: 0;
}

.timeline__list::before {
    content: "";
    position: absolute;
    left: 1.25rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #d0d0d0;
}

/* Individual item */
.timeline__item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 2rem;
}

/* Marker (icon inside a circle) */
.timeline__marker {
    position: relative;
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    margin-right: 1rem;
}

.marker__icon {
    font-size: 1.5rem;
    color: #fff;
    background: #007aff;
    border-radius: 50%;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Content block */
.timeline__content {
    flex: 1;
}

/* Header (date + origin) */
.content__header {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
}

.header__date {
    font-weight: 600;
    color: #333;
}

.header__origin {
    color: #555;
}
.header__url {
    font-style: italic;
    color: #555;
}

/* Summary paragraph – clamp to 4 lines when not expanded */
.content__summary {
    margin: 0;
    line-height: 1.45;
    color: #222;
    overflow: hidden;
    transition: max-height 0.2s ease;
}

.content__summary.truncated {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    /* number of lines to show */
    -webkit-box-orient: vertical;
}

/* More / Less button */
.summary__toggle {
    margin-top: 0.4rem;
    background: none;
    border: none;
    color: #007aff;
    cursor: pointer;
    font-weight: 500;
    padding: 0;
}

.summary__toggle:hover {
    text-decoration: underline;
}
</style>
