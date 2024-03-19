import loader from "../../assets/img/loader.svg"
import loaderDark from "../../assets/img/loader_dark.svg"

interface IProps {
  darkMode?: boolean
}

function Loader(props: IProps) {
  const { darkMode } = props

  return (
    <img
      style={{
        display: "block",
      }}
      src={darkMode ? loaderDark : loader}
      alt="Loading..."
      width={34}
      height={34}
    />
  )
}

export default Loader
