import { Card, CardBody, CardFooter, Image } from "@heroui/react";
import type { Product } from "@/types/product";
import { formatDate, formatPrice } from "@/lib/format";

const FALLBACK_IMAGE =
  "data:image/svg+xml;utf8," +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' +
      '<rect width="400" height="300" fill="#e6e8eb"/>' +
      '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" ' +
      'font-family="monospace" font-size="18" fill="#5a616c">no image</text></svg>',
  );

export function ProductCard({ product }: { product: Product }) {
  return (
    <Card
      radius="sm"
      className="overflow-hidden border border-rule bg-card transition-colors hover:border-ink"
    >
      <CardBody className="p-0">
        <div className="aspect-[4/3] w-full overflow-hidden bg-paper">
          <Image
            removeWrapper
            alt={product.title}
            src={product.image_url}
            fallbackSrc={FALLBACK_IMAGE}
            radius="none"
            className="h-full w-full object-cover"
          />
        </div>
      </CardBody>
      <CardFooter className="flex-col items-start gap-3 p-4">
        <h2 className="line-clamp-2 text-body text-ink">{product.title}</h2>
        <span className="font-mono text-price tabular-nums text-ink">
          {formatPrice(product.price)}
        </span>
        <div className="flex w-full items-center justify-between border-t border-rule pt-3 font-mono text-meta tabular-nums text-muted">
          <span>#{String(product.id).padStart(4, "0")}</span>
          <span>{formatDate(product.created_at)}</span>
        </div>
      </CardFooter>
    </Card>
  );
}
