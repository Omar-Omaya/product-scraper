package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
)

// Config is the on-disk shape of proxies.json.
type Config struct {
	Proxies    []ProxyConfig `json:"proxies"`
	UserAgents []string      `json:"user_agents"`
}

// ProxyConfig is a single proxy entry.
type ProxyConfig struct {
	URL string `json:"url"`
}

// LoadConfig reads and validates the config file. It fails fast: a missing
// file, malformed JSON, or an empty list is returned as an error rather than
// starting the service in an unusable state.
func LoadConfig(path string) (Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return Config{}, fmt.Errorf("reading config %s: %w", path, err)
	}

	var cfg Config
	if err := json.Unmarshal(data, &cfg); err != nil {
		return Config{}, fmt.Errorf("parsing config %s: %w", path, err)
	}

	if len(cfg.Proxies) == 0 {
		return Config{}, errors.New("config has no proxies")
	}
	if len(cfg.UserAgents) == 0 {
		return Config{}, errors.New("config has no user_agents")
	}
	for i, p := range cfg.Proxies {
		if p.URL == "" {
			return Config{}, fmt.Errorf("proxy at index %d has an empty url", i)
		}
	}

	return cfg, nil
}

// proxyURLs flattens the proxy entries into their URLs.
func (c Config) proxyURLs() []string {
	urls := make([]string, len(c.Proxies))
	for i, p := range c.Proxies {
		urls[i] = p.URL
	}
	return urls
}
