"use client";

import { useEffect, useMemo, useState } from "react";
import { Alert, Button, Input, Pagination } from "@heroui/react";
import { useProducts } from "@/hooks/use-products";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { ProductsHeader } from "@/components/products/products-header";
import { ProductCard } from "@/components/products/product-card";
import { ProductCardSkeleton } from "@/components/products/product-card-skeleton";

const PAGE_SIZE = 12;
const SKELETON_COUNT = 8;
const GRID = "grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4";

export default function Home() {
  const { products, error, isFetching, lastUpdated, reload } = useProducts();

  const [query, setQuery] = useState("");
  const debouncedQuery = useDebouncedValue(query, 300);
  const [page, setPage] = useState(1);

  // A changed search always restarts from the first page.
  useEffect(() => {
    setPage(1);
  }, [debouncedQuery]);

  const filtered = useMemo(() => {
    if (!products) return [];
    const q = debouncedQuery.trim().toLowerCase();
    if (!q) return products;
    return products.filter((p) => p.title.toLowerCase().includes(q));
  }, [products, debouncedQuery]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));

  // When the result set shrinks (e.g. a narrower search), pull a now-out-of-range
  // page back into bounds.
  useEffect(() => {
    if (page > totalPages) setPage(totalPages);
  }, [page, totalPages]);

  const pageItems = useMemo(
    () => filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE),
    [filtered, page],
  );

  const handlePageChange = (next: number) => {
    setPage(next);
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    window.scrollTo({ top: 0, behavior: reduced ? "auto" : "smooth" });
  };

  let content;
  if (products === null && !error) {
    content = (
      <div className={GRID}>
        {Array.from({ length: SKELETON_COUNT }, (_, i) => (
          <ProductCardSkeleton key={i} />
        ))}
      </div>
    );
  } else if (error && (products === null || products.length === 0)) {
    // Only surface the full error state when there is nothing to show. A failed
    // background refresh with products still on screen falls through to the grid.
    content = (
      <Alert
        color="danger"
        variant="bordered"
        radius="sm"
        title="Could not load products"
        description={error}
        endContent={
          <Button color="danger" variant="flat" size="sm" radius="sm" onPress={reload}>
            Retry
          </Button>
        }
      />
    );
  } else if (products && products.length === 0) {
    content = (
      <Alert
        color="default"
        variant="bordered"
        radius="sm"
        title="No products yet"
        description="Run the scraper to populate this list, then records will appear here."
      />
    );
  } else if (filtered.length === 0) {
    content = (
      <Alert
        color="default"
        variant="bordered"
        radius="sm"
        title="Nothing matched"
        description={`No records match "${debouncedQuery.trim()}".`}
        endContent={
          <Button variant="flat" size="sm" radius="sm" onPress={() => setQuery("")}>
            Clear search
          </Button>
        }
      />
    );
  } else {
    content = (
      <>
        <div className={GRID}>
          {pageItems.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
        {totalPages > 1 && (
          <div className="mt-10 flex justify-center">
            <Pagination
              total={totalPages}
              page={page}
              onChange={handlePageChange}
              radius="sm"
              variant="bordered"
              showControls
            />
          </div>
        )}
      </>
    );
  }

  return (
    <div className="flex flex-1 flex-col">
      <ProductsHeader
        total={products?.length ?? 0}
        lastUpdated={lastUpdated}
        isFetching={isFetching}
      />

      <div className="border-b border-rule">
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <Input
            isClearable
            value={query}
            onValueChange={setQuery}
            onClear={() => setQuery("")}
            variant="bordered"
            radius="sm"
            placeholder="Filter by title"
            aria-label="Filter products by title"
            className="max-w-md"
            classNames={{
              inputWrapper: "border-rule bg-card data-[hover=true]:border-ink",
              input: "text-body",
            }}
          />
          {products && products.length > 0 && debouncedQuery.trim() !== "" && (
            <span aria-live="polite" className="font-mono text-meta tabular-nums text-muted">
              {filtered.length} of {products.length} match
            </span>
          )}
        </div>
      </div>

      <div className="mx-auto w-full max-w-7xl px-4 py-8">{content}</div>
    </div>
  );
}
