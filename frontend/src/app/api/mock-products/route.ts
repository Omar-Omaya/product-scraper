import { readFile } from "node:fs/promises";
import path from "node:path";
import { NextResponse } from "next/server";
import type { ProductsResponse } from "@/types/product";

export async function GET() {
  const file = path.join(process.cwd(), "mock", "products.json");
  const contents = await readFile(file, "utf-8");
  const body: ProductsResponse = JSON.parse(contents);
  return NextResponse.json(body);
}
