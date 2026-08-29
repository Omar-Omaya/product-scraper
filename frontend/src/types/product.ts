export interface Product {
  id: number;
  title: string;
  price: string;
  image_url: string;
  created_at: string;
}

export interface ProductsResponse {
  data: Product[];
}
