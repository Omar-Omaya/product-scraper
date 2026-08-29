package pool

import (
	"errors"
	"log"
	"math/rand/v2"
	"sync"
)

const maxFailures = 3

var (
	ErrNoHealthyProxies = errors.New("no healthy proxies available")
	ErrUnknownProxy = errors.New("unknown proxy")
)

type proxy struct {
	url      string
	failures int
	healthy  bool
}

type Pool struct {
	mu         sync.RWMutex
	proxies    []*proxy
	userAgents []string
	next       int
}

type Selection struct {
	Proxy     string
	UserAgent string
}

type Stats struct {
	Total     int
	Healthy   int
	Unhealthy int
}

func New(proxyURLs, userAgents []string) (*Pool, error) {
	if len(proxyURLs) == 0 {
		return nil, errors.New("pool: no proxies")
	}
	if len(userAgents) == 0 {
		return nil, errors.New("pool: no user agents")
	}

	proxies := make([]*proxy, len(proxyURLs))
	for i, u := range proxyURLs {
		proxies[i] = &proxy{url: u, healthy: true}
	}

	agents := make([]string, len(userAgents))
	copy(agents, userAgents)

	return &Pool{proxies: proxies, userAgents: agents}, nil
}

func (p *Pool) Next() (Selection, error) {
	p.mu.Lock()
	defer p.mu.Unlock()

	n := len(p.proxies)
	for i := 0; i < n; i++ {
		idx := (p.next + i) % n
		if px := p.proxies[idx]; px.healthy {
			p.next = (idx + 1) % n
			return Selection{Proxy: px.url, UserAgent: p.userAgents[rand.IntN(len(p.userAgents))]}, nil
		}
	}
	return Selection{}, ErrNoHealthyProxies
}

func (p *Pool) Report(url string, success bool) error {
	p.mu.Lock()
	defer p.mu.Unlock()

	px := p.find(url)
	if px == nil {
		return ErrUnknownProxy
	}

	if success {
		px.failures = 0
		if !px.healthy {
			px.healthy = true
			log.Printf("proxy %s recovered, marked healthy", px.url)
		}
		return nil
	}

	px.failures++
	if px.healthy && px.failures >= maxFailures {
		px.healthy = false
		log.Printf("proxy %s marked unhealthy after %d consecutive failures", px.url, px.failures)
	}
	return nil
}

func (p *Pool) Health() Stats {
	p.mu.RLock()
	defer p.mu.RUnlock()

	healthy := 0
	for _, px := range p.proxies {
		if px.healthy {
			healthy++
		}
	}
	return Stats{Total: len(p.proxies), Healthy: healthy, Unhealthy: len(p.proxies) - healthy}
}

func (p *Pool) find(url string) *proxy {
	for _, px := range p.proxies {
		if px.url == url {
			return px
		}
	}
	return nil
}
