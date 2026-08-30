import { useCallback, useEffect, useRef, useState } from "react";
import type { Product, ProductsResponse } from "@/types/product";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "/api/mock-products";
const SCRAPE_URL = `${API_URL}/scrape`;
const REFRESH_INTERVAL = 30_000;
const PER_PAGE = 48;
const SCRAPE_LIMIT = 8;

interface UseProducts {
  products: Product[] | null;
  total: number;
  error: string | null;
  isScraping: boolean;
  lastUpdated: Date | null;
  refresh: () => void;
}

export function useProducts(query: string): UseProducts {
  const [products, setProducts] = useState<Product[] | null>(null);
  const [total, setTotal] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [isScraping, setIsScraping] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const queryRef = useRef(query);
  queryRef.current = query;

  const firstRun = useRef(true);

  const fetchProducts = useCallback(async () => {
    const q = encodeURIComponent(queryRef.current.trim());
    const res = await fetch(`${API_URL}?q=${q}&per_page=${PER_PAGE}`, { cache: "no-store" });
    if (!res.ok) {
      throw new Error(`Request failed with status ${res.status}`);
    }
    const body: ProductsResponse = await res.json();
    setProducts(body.data);
    setTotal(body.meta?.total ?? body.data.length);
    setLastUpdated(new Date());
    setError(null);
  }, []);

  const scrapeThenFetch = useCallback(async () => {
    setIsScraping(true);
    try {
      try {
        await fetch(SCRAPE_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ q: queryRef.current.trim(), limit: SCRAPE_LIMIT }),
        });
      } catch {

      }
      await fetchProducts();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Something went wrong while loading products",
      );
    } finally {
      setIsScraping(false);
    }
  }, [fetchProducts]);

  useEffect(() => {
    let active = true;

    if (firstRun.current) {
      firstRun.current = false;
      fetchProducts().catch(() => {});
    } else {
      setProducts(null);
    }
    scrapeThenFetch();

    const id = setInterval(() => {
      if (active) scrapeThenFetch();
    }, REFRESH_INTERVAL);

    return () => {
      active = false;
      clearInterval(id);
    };
  }, [query, fetchProducts, scrapeThenFetch]);

  return { products, total, error, isScraping, lastUpdated, refresh: scrapeThenFetch };
}
