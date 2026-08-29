import { useCallback, useEffect, useState } from "react";
import type { Product, ProductsResponse } from "@/types/product";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "/api/mock-products";
const REFRESH_INTERVAL = 30_000;

interface UseProducts {
  products: Product[] | null;
  error: string | null;
  isFetching: boolean;
  lastUpdated: Date | null;
  reload: () => void;
}

export function useProducts(): UseProducts {
  const [products, setProducts] = useState<Product[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isFetching, setIsFetching] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const load = useCallback(async () => {
    setIsFetching(true);
    try {
      const res = await fetch(API_URL, { cache: "no-store" });
      if (!res.ok) {
        throw new Error(`Request failed with status ${res.status}`);
      }
      const body: ProductsResponse = await res.json();
      setProducts(body.data);
      setLastUpdated(new Date());
      setError(null);
    } catch (err) {
      setError(
        err instanceof Error
          ? err.message
          : "Something went wrong while loading products",
      );
    } finally {
      setIsFetching(false);
    }
  }, []);

  useEffect(() => {
    load();
    const id = setInterval(load, REFRESH_INTERVAL);
    return () => clearInterval(id);
  }, [load]);

  return { products, error, isFetching, lastUpdated, reload: load };
}
