package pool

import (
	"errors"
	"testing"
)

func markUnhealthy(t *testing.T, p *Pool, url string) {
	t.Helper()
	for i := 0; i < maxFailures; i++ {
		if err := p.Report(url, false); err != nil {
			t.Fatalf("Report(%q, false): %v", url, err)
		}
	}
}

func TestNextRoundRobinSkipsUnhealthy(t *testing.T) {
	p, err := New([]string{"a", "b", "c"}, []string{"ua"})
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	markUnhealthy(t, p, "b")

	want := []string{"a", "c", "a", "c"}
	for i, w := range want {
		sel, err := p.Next()
		if err != nil {
			t.Fatalf("Next call %d: %v", i, err)
		}
		if sel.Proxy != w {
			t.Errorf("Next call %d = %q, want %q", i, sel.Proxy, w)
		}
	}
}

func TestReportFailureThreshold(t *testing.T) {
	tests := []struct {
		name        string
		failures    int
		wantHealthy int
	}{
		{"one failure stays healthy", 1, 1},
		{"two failures stay healthy", 2, 1},
		{"three failures marks unhealthy", 3, 0},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			p, err := New([]string{"a"}, []string{"ua"})
			if err != nil {
				t.Fatalf("New: %v", err)
			}
			for i := 0; i < tt.failures; i++ {
				if err := p.Report("a", false); err != nil {
					t.Fatalf("Report: %v", err)
				}
			}
			if got := p.Health().Healthy; got != tt.wantHealthy {
				t.Errorf("healthy count = %d, want %d", got, tt.wantHealthy)
			}
		})
	}
}

func TestSuccessResetsFailureCount(t *testing.T) {
	p, err := New([]string{"a"}, []string{"ua"})
	if err != nil {
		t.Fatalf("New: %v", err)
	}

	p.Report("a", false)
	p.Report("a", false)
	p.Report("a", true)

	p.Report("a", false)
	p.Report("a", false)
	if got := p.Health().Healthy; got != 1 {
		t.Fatalf("after reset and two failures, healthy = %d, want 1", got)
	}

	p.Report("a", false)
	if got := p.Health().Healthy; got != 0 {
		t.Fatalf("after three consecutive failures, healthy = %d, want 0", got)
	}
}

func TestNextAllUnhealthy(t *testing.T) {
	p, err := New([]string{"a", "b"}, []string{"ua"})
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	markUnhealthy(t, p, "a")
	markUnhealthy(t, p, "b")

	if _, err := p.Next(); !errors.Is(err, ErrNoHealthyProxies) {
		t.Fatalf("Next error = %v, want ErrNoHealthyProxies", err)
	}
}

func TestReportUnknownProxy(t *testing.T) {
	p, err := New([]string{"a"}, []string{"ua"})
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	if err := p.Report("nope", false); !errors.Is(err, ErrUnknownProxy) {
		t.Fatalf("Report error = %v, want ErrUnknownProxy", err)
	}
}
