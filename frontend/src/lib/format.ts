const priceFormatter = new Intl.NumberFormat("en-EG", {
  style: "currency",
  currency: "EGP",
});

export function formatPrice(price: string) {
  const value = Number(price);
  return Number.isNaN(value) ? price : priceFormatter.format(value);
}

export function formatDate(iso: string) {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleDateString([], { year: "numeric", month: "short", day: "2-digit" });
}

export function formatTime(date: Date) {
  return date.toLocaleTimeString([], { hour12: false });
}
