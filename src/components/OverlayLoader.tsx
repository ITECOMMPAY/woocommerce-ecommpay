import {useEffect, useMemo} from "@wordpress/element"
import useDelayUnmount from "../hooks/useDelayUnmount"
import Loader from "./Loader"

interface IProps {
  show: boolean
}

declare global {
  interface Window {
    ECP: any;
  }
}

const ANIMATION_DURATION = 300

function OverlayLoader(props: IProps) {
  const { show } = props

  const shouldRender = useDelayUnmount(show, ANIMATION_DURATION)

  const isDarkMode = useMemo(
    () =>
      window.ECP.darkMode ??
      window.matchMedia("(prefers-color-scheme: dark)").matches,
    []
  )

  useEffect(() => {
    document.body.style.overflow = show ? "hidden" : "auto"

    return () => {
      document.body.style.overflow = "auto"
    }
  }, [show])

  return (
    <>
      <style>
        {`
          @keyframes fade-in {
            from {
              opacity: 0;
            }
            to {
              opacity: 1;
            }
          }
        `}
      </style>
      {shouldRender && (
        <div
          id="ecommpay-loader-embedded"
          style={{
            display: "flex",
            position: "fixed",
            top: 0,
            left: 0,
            width: "100%",
            height: "100%",
            flexDirection: "column",
            justifyContent: "center",
            alignItems: "center",
            gap: "10px",
            backgroundColor: isDarkMode
              ? "rgba(0, 0, 0, 0.9)"
              : "rgba(255, 255, 255, 0.9)",
            opacity: show ? 1 : 0,
            animation: `fade-in ${ANIMATION_DURATION}ms`,
            transition: "opacity 500ms",
            zIndex: 9999,
          }}
        >
          <Loader darkMode={isDarkMode} />
        </div>
      )}
    </>
  )
}

export default OverlayLoader
