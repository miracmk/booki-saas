import React, { useRef, useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { BiometricMetadata } from "@shared/legalTypes";
import { Eraser, Undo2, Check, PenTool } from "lucide-react";

interface DigitalSignaturePadProps {
  onSignatureComplete?: (data: {
    base64Png: string;
    svgString: string;
    biometrics: BiometricMetadata;
  }) => void;
  disabled?: boolean;
  className?: string;
}

export const DigitalSignaturePad: React.FC<DigitalSignaturePadProps> = ({
  onSignatureComplete,
  disabled = false,
  className = "",
}) => {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [strokeHistory, setStrokeHistory] = useState<ImageData[]>([]);
  const [strokeCount, setStrokeCount] = useState(0);
  const [pointCount, setPointCount] = useState(0);
  const [startTime, setStartTime] = useState<number | null>(null);
  const [endTime, setEndTime] = useState<number | null>(null);
  const [hasDrawn, setHasDrawn] = useState(false);

  useEffect(() => {
    initCanvas();
  }, []);

  const initCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    const ratio = Math.max(window.devicePixelRatio || 1, 2);
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.scale(ratio, ratio);

    ctx.strokeStyle = "#0f172a";
    ctx.lineWidth = 2.4;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";

    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, rect.width, rect.height);

    setStrokeHistory([ctx.getImageData(0, 0, canvas.width, canvas.height)]);
  };

  const getCanvasCoords = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    if ("touches" in e) {
      const touch = e.touches[0];
      return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top,
      };
    }
    return {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top,
    };
  };

  const startDrawing = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    if (disabled) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    if (!startTime) {
      setStartTime(Date.now());
    }

    setIsDrawing(true);
    setStrokeCount((prev) => prev + 1);
    setPointCount((prev) => prev + 1);

    const { x, y } = getCanvasCoords(e);
    ctx.beginPath();
    ctx.moveTo(x, y);
  };

  const draw = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    if (!isDrawing || disabled) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    setPointCount((prev) => prev + 1);
    const { x, y } = getCanvasCoords(e);
    ctx.lineTo(x, y);
    ctx.stroke();
    setHasDrawn(true);
  };

  const stopDrawing = () => {
    if (!isDrawing || disabled) return;
    setIsDrawing(false);
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    setEndTime(Date.now());
    setStrokeHistory((prev) => [...prev, ctx.getImageData(0, 0, canvas.width, canvas.height)]);

    notifyCompletion();
  };

  const notifyCompletion = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const base64Png = canvas.toDataURL("image/png");
    const rect = canvas.getBoundingClientRect();
    const durationMs = (Date.now() - (startTime || Date.now()));

    const biometrics: BiometricMetadata = {
      strokeCount,
      durationMs: Math.max(durationMs, 100),
      pointCount,
      devicePixelRatio: window.devicePixelRatio || 1,
      screenResolution: `${window.innerWidth}x${window.innerHeight}`,
    };

    // Lightweight SVG representation
    const svgString = `<svg xmlns="http://www.w3.org/2000/svg" width="${rect.width}" height="${rect.height}"><image href="${base64Png}" width="${rect.width}" height="${rect.height}"/></svg>`;

    onSignatureComplete?.({
      base64Png,
      svgString,
      biometrics,
    });
  };

  const clearCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    const rect = canvas.getBoundingClientRect();
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, rect.width, rect.height);
    setHasDrawn(false);
    setStrokeCount(0);
    setPointCount(0);
    setStartTime(null);
    setEndTime(null);
    setStrokeHistory([ctx.getImageData(0, 0, canvas.width, canvas.height)]);
  };

  const undoCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas || strokeHistory.length <= 1) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    const newHistory = strokeHistory.slice(0, -1);
    const prevState = newHistory[newHistory.length - 1];
    ctx.putImageData(prevState, 0, 0);
    setStrokeHistory(newHistory);
    if (newHistory.length <= 1) {
      setHasDrawn(false);
    }
  };

  return (
    <div className={`space-y-2 ${className}`}>
      <div className="flex items-center justify-between">
        <Label className="text-xs font-medium text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
          <PenTool className="w-3.5 h-3.5 text-emerald-600" />
          <span>Biyometrik Kanvas İmza (HMK 199 / 5070 E-İmza)</span>
        </Label>
        <div className="flex items-center gap-1">
          <Button
            type="button"
            size="sm"
            variant="ghost"
            disabled={disabled || strokeHistory.length <= 1}
            className="h-6 px-2 text-[11px] text-slate-500 gap-1"
            onClick={undoCanvas}
          >
            <Undo2 className="w-3 h-3" /> Geri Al
          </Button>
          <Button
            type="button"
            size="sm"
            variant="ghost"
            disabled={disabled || !hasDrawn}
            className="h-6 px-2 text-[11px] text-rose-500 hover:text-rose-600 gap-1"
            onClick={clearCanvas}
          >
            <Eraser className="w-3 h-3" /> Temizle
          </Button>
        </div>
      </div>

      <div className="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-lg overflow-hidden bg-white shadow-inner">
        <canvas
          ref={canvasRef}
          onMouseDown={startDrawing}
          onMouseMove={draw}
          onMouseUp={stopDrawing}
          onMouseLeave={stopDrawing}
          onTouchStart={startDrawing}
          onTouchMove={draw}
          onTouchEnd={stopDrawing}
          className={`w-full h-28 touch-none cursor-crosshair block ${
            disabled ? "opacity-40 cursor-not-allowed" : ""
          }`}
        />

        <div className="absolute bottom-1 right-2 pointer-events-none flex items-center gap-2 text-[9px] text-slate-400 font-mono select-none">
          <span>Vuruş: {strokeCount}</span>
          <span>Nokta: {pointCount}</span>
          <span>BooKi Biometric Pad</span>
        </div>
      </div>
    </div>
  );
};
