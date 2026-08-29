package main

import (
	"encoding/json"
	"errors"
	"log"
	"net/http"

	"proxy-service/pool"
)

type server struct {
	pool *pool.Pool
}

func newServer(p *pool.Pool) *server {
	return &server{pool: p}
}

func (s *server) routes() *http.ServeMux {
	mux := http.NewServeMux()
	mux.HandleFunc("GET /proxy/next", s.handleNext)
	mux.HandleFunc("POST /proxy/report", s.handleReport)
	mux.HandleFunc("GET /health", s.handleHealth)
	return mux
}

type nextResponse struct {
	Proxy     string `json:"proxy"`
	UserAgent string `json:"user_agent"`
}

func (s *server) handleNext(w http.ResponseWriter, _ *http.Request) {
	sel, err := s.pool.Next()
	if err != nil {
		writeError(w, http.StatusServiceUnavailable, "no healthy proxies available")
		return
	}
	writeJSON(w, http.StatusOK, nextResponse{Proxy: sel.Proxy, UserAgent: sel.UserAgent})
}

type reportRequest struct {
	Proxy string `json:"proxy"`
	Success *bool `json:"success"`
}

func (s *server) handleReport(w http.ResponseWriter, r *http.Request) {
	var req reportRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid JSON body")
		return
	}
	if req.Proxy == "" {
		writeError(w, http.StatusBadRequest, "proxy is required")
		return
	}
	if req.Success == nil {
		writeError(w, http.StatusBadRequest, "success is required")
		return
	}

	switch err := s.pool.Report(req.Proxy, *req.Success); {
	case errors.Is(err, pool.ErrUnknownProxy):
		writeError(w, http.StatusNotFound, "unknown proxy")
	case err != nil:
		writeError(w, http.StatusInternalServerError, "could not record report")
	default:
		w.WriteHeader(http.StatusNoContent)
	}
}

type healthResponse struct {
	Total     int `json:"total"`
	Healthy   int `json:"healthy"`
	Unhealthy int `json:"unhealthy"`
}

func (s *server) handleHealth(w http.ResponseWriter, _ *http.Request) {
	st := s.pool.Health()
	writeJSON(w, http.StatusOK, healthResponse{Total: st.Total, Healthy: st.Healthy, Unhealthy: st.Unhealthy})
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(v); err != nil {
		log.Printf("writing response: %v", err)
	}
}

func writeError(w http.ResponseWriter, status int, msg string) {
	writeJSON(w, status, map[string]string{"error": msg})
}
