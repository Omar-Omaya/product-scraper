package main

import (
	"encoding/json"
	"fmt"
	"net/url"
	"os"
	"strconv"
	"strings"
)

type proxyEntry struct {
	URL string `json:"url"`
}

type config struct {
	Proxies    []proxyEntry `json:"proxies"`
	UserAgents []string     `json:"user_agents"`
}

var defaultUserAgents = []string{
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15",
	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
}

func main() {
	if err := run(); err != nil {
		fmt.Fprintln(os.Stderr, "genproxies:", err)
		os.Exit(1)
	}
}

func run() error {
	host := os.Getenv("PROXY_HOST")
	if host == "" {
		return fmt.Errorf("PROXY_HOST is required (e.g. gate.provider.com); also set PROXY_PORTS and optionally PROXY_USER, PROXY_PASS, PROXY_SCHEME")
	}

	ports, err := parsePorts(os.Getenv("PROXY_PORTS"))
	if err != nil {
		return err
	}

	scheme := envOr("PROXY_SCHEME", "http")
	out := envOr("PROXY_OUTPUT", "proxies.json")

	auth := ""
	if user := os.Getenv("PROXY_USER"); user != "" {
		auth = url.UserPassword(user, os.Getenv("PROXY_PASS")).String() + "@"
	}

	proxies := make([]proxyEntry, 0, len(ports))
	for _, p := range ports {
		proxies = append(proxies, proxyEntry{URL: fmt.Sprintf("%s://%s%s:%d", scheme, auth, host, p)})
	}

	cfg := config{Proxies: proxies, UserAgents: userAgents(out)}

	data, err := json.MarshalIndent(cfg, "", "  ")
	if err != nil {
		return err
	}
	data = append(data, '\n')

	if err := os.WriteFile(out, data, 0o600); err != nil {
		return fmt.Errorf("writing %s: %w", out, err)
	}

	fmt.Printf("wrote %s with %d proxies and %d user-agents\n", out, len(proxies), len(cfg.UserAgents))
	return nil
}

func parsePorts(spec string) ([]int, error) {
	spec = strings.TrimSpace(spec)
	if spec == "" {
		return nil, fmt.Errorf(`PROXY_PORTS is required (a port "7000", a range "10000-10009", or a list "10000,10001")`)
	}

	var ports []int
	for _, part := range strings.Split(spec, ",") {
		part = strings.TrimSpace(part)
		if lo, hi, ok := strings.Cut(part, "-"); ok {
			low, err1 := strconv.Atoi(strings.TrimSpace(lo))
			high, err2 := strconv.Atoi(strings.TrimSpace(hi))
			if err1 != nil || err2 != nil || low > high {
				return nil, fmt.Errorf("invalid port range %q", part)
			}
			for p := low; p <= high; p++ {
				ports = append(ports, p)
			}
			continue
		}
		p, err := strconv.Atoi(part)
		if err != nil {
			return nil, fmt.Errorf("invalid port %q", part)
		}
		ports = append(ports, p)
	}
	return ports, nil
}

func userAgents(path string) []string {
	if data, err := os.ReadFile(path); err == nil {
		var existing config
		if json.Unmarshal(data, &existing) == nil && len(existing.UserAgents) > 0 {
			return existing.UserAgents
		}
	}
	return defaultUserAgents
}

func envOr(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
