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
  const [query, setQuery] = useState("");
  const debouncedQuery = useDebouncedValue(query, 400);
  const { products, total, error, isScraping, lastUpdated, refresh } = useProducts(debouncedQuery);
  const [page, setPage] = useState(1);

  useEffect(() => {
    setPage(1);
  }, [debouncedQuery, products?.length]);

  const totalPages = Math.max(1, Math.ceil((products?.length ?? 0) / PAGE_SIZE));

  useEffect(() => {
    if (page > totalPages) setPage(totalPages);
  }, [page, totalPages]);

  const pageItems = useMemo(
    () => (products ?? []).slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE),
    [products, page],
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
    content = (
      <Alert
        color="danger"
        variant="bordered"
        radius="sm"
        title="Could not load products"
        description={error}
        endContent={
          <Button color="danger" variant="flat" size="sm" radius="sm" onPress={refresh}>
            Retry
          </Button>
        }
      />
    );
  } else if (products && products.length === 0 && debouncedQuery.trim() !== "") {
    content = (
      <Alert
        color="default"
        variant="bordered"
        radius="sm"
        title={`No results for "${debouncedQuery.trim()}" yet`}
        description="Scraping this filter now — matching products will appear shortly."
        endContent={
          <Button variant="flat" size="sm" radius="sm" onPress={() => setQuery("")}>
            Clear filter
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
        description="Scraping the catalogue now — this will fill in shortly."
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
      <ProductsHeader total={total} lastUpdated={lastUpdated} isFetching={isScraping} />

      <div className="border-b border-rule">
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <Input
            isClearable
            value={query}
            onValueChange={setQuery}
            onClear={() => setQuery("")}
            variant="bordered"
            radius="sm"
            placeholder="Filter and scrape by title, e.g. laptop"
            aria-label="Filter and scrape products by title"
            className="max-w-md"
            classNames={{
              inputWrapper: "border-rule bg-card data-[hover=true]:border-ink",
              input: "text-body",
            }}
          />
          {isScraping && (
            <span aria-live="polite" className="font-mono text-meta text-muted">
              scraping…
            </span>
          )}
        </div>
      </div>

      <div className="mx-auto w-full max-w-7xl px-4 py-8">{content}</div>
    </div>
  );
}
