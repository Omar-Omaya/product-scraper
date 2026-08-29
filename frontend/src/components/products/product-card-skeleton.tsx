import { Card, CardBody, CardFooter, Skeleton } from "@heroui/react";

export function ProductCardSkeleton() {
  return (
    <Card shadow="none" radius="sm" className="overflow-hidden border border-rule bg-card">
      <CardBody className="p-0">
        <Skeleton className="aspect-[4/3] w-full" />
      </CardBody>
      <CardFooter className="flex-col items-start gap-3 p-4">
        <Skeleton className="h-4 w-4/5 rounded-none" />
        <Skeleton className="h-4 w-3/5 rounded-none" />
        <Skeleton className="h-6 w-2/5 rounded-none" />
        <div className="flex w-full items-center justify-between border-t border-rule pt-3">
          <Skeleton className="h-3 w-10 rounded-none" />
          <Skeleton className="h-3 w-20 rounded-none" />
        </div>
      </CardFooter>
    </Card>
  );
}
