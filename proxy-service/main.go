package main

import (
	"log"
	"net/http"
	"os"

	"proxy-service/pool"
)

func main() {
	configPath := getenv("CONFIG_PATH", "proxies.json")
	cfg, err := LoadConfig(configPath)
	if err != nil {
		log.Fatalf("config: %v", err)
	}

	p, err := pool.New(cfg.proxyURLs(), cfg.UserAgents)
	if err != nil {
		log.Fatalf("pool: %v", err)
	}

	port := getenv("PORT", "8081")
	srv := newServer(p)

	log.Printf("proxy-service listening on :%s (%d proxies, %d user-agents loaded)",
		port, len(cfg.Proxies), len(cfg.UserAgents))

	if err := http.ListenAndServe(":"+port, srv.routes()); err != nil {
		log.Fatalf("server: %v", err)
	}
}

func getenv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
