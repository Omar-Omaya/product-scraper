import { formatTime } from "@/lib/format";

interface ProductsHeaderProps {
  total: number;
  lastUpdated: Date | null;
  isFetching: boolean;
}

export function ProductsHeader({ total, lastUpdated, isFetching }: ProductsHeaderProps) {
  return (
    <header className="bg-ink text-paper">
      <div className="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-6 sm:flex-row sm:items-end sm:justify-between">
        <h1 className="font-display text-display text-white">Product Scraper</h1>
        <div className="flex items-center gap-2 font-mono text-meta tabular-nums text-paper/70">
          <span aria-live="polite">
            {total} {total === 1 ? "record" : "records"}
          </span>
          <span aria-hidden className="text-paper/30">
            /
          </span>
          <span>updated {lastUpdated ? formatTime(lastUpdated) : "--:--:--"}</span>
          <span
            role="status"
            aria-label={isFetching ? "Refreshing" : "Idle"}
            className={`ml-1 inline-block size-2 rounded-full transition-colors ${
              isFetching ? "bg-signal motion-safe:animate-pulse" : "bg-paper/20"
            }`}
          />
        </div>
      </div>
    </header>
  );
}
