export interface Product {
  id: number;
  title: string;
  price: string;
  image_url: string;
  created_at: string;
}

export interface ProductsMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ProductsResponse {
  data: Product[];
  meta?: ProductsMeta;
}
